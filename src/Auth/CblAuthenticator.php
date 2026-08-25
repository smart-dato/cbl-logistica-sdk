<?php

namespace SmartDato\CblLogistica\Auth;

use Carbon\CarbonImmutable;
use Saloon\Contracts\Authenticator;
use Saloon\Http\PendingRequest;
use Saloon\Repositories\Body\ArrayBodyRepository;
use SmartDato\CblLogistica\Auth\Requests\GetDailyTokenRequest;
use SmartDato\CblLogistica\Contracts\TokenStore;
use SmartDato\CblLogistica\Data\Credentials;
use SmartDato\CblLogistica\Exceptions\CblLogisticaApiException;

/**
 * CBL wants two things on every call: HTTP basic auth, and a dailyToken inside the
 * JSON body. Saloon merges the request body before it authenticates the pending
 * request, so the token can be injected here — which keeps dailyToken out of every
 * DTO the caller has to build.
 */
class CblAuthenticator implements Authenticator
{
    public function __construct(
        protected readonly Credentials $credentials,
        protected readonly TokenStore $tokenStore,
    ) {}

    public function set(PendingRequest $pendingRequest): void
    {
        $pendingRequest->headers()->add('Authorization', 'Basic '.base64_encode(
            $this->credentials->username.':'.$this->credentials->password,
        ));

        $body = $pendingRequest->body();

        if ($body instanceof ArrayBodyRepository) {
            $body->add('dailyToken', $this->dailyToken());
        }
    }

    public function dailyToken(): string
    {
        $key = $this->cacheKey();
        $cached = $this->tokenStore->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $token = $this->fetchDailyToken();

        $this->tokenStore->put($key, $token, $this->secondsUntilEndOfDay());

        return $token;
    }

    public function cacheKey(): string
    {
        $prefix = (string) config('cbl-logistica-sdk.cache.prefix', 'cbl-logistica:daily-token');

        return $prefix.':'.$this->credentials->fingerprint();
    }

    protected function fetchDailyToken(): string
    {
        $connector = $this->tokenConnector();
        $connector->authenticate(new BasicCredentialsAuthenticator($this->credentials));

        $token = $connector
            ->send(new GetDailyTokenRequest($this->credentials->clientToken))
            ->json('dailyToken');

        if (! is_string($token) || $token === '') {
            throw new CblLogisticaApiException('CBL returned no dailyToken for client code '.$this->credentials->clientCode.'.');
        }

        return $token;
    }

    protected function tokenConnector(): TokenConnector
    {
        return new TokenConnector;
    }

    /**
     * The token is valid for the calendar day it was issued on, so it is cached
     * until midnight rather than for a fixed window.
     */
    protected function secondsUntilEndOfDay(): int
    {
        $now = CarbonImmutable::now();

        // Carbon 3 returns a float here, and a cache TTL is whole seconds.
        return max(1, (int) $now->diffInSeconds($now->endOfDay()));
    }
}
