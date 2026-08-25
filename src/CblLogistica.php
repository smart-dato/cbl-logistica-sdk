<?php

namespace SmartDato\CblLogistica;

use SmartDato\CblLogistica\Auth\CblAuthenticator;
use SmartDato\CblLogistica\Cache\ArrayTokenStore;
use SmartDato\CblLogistica\Connectors\CblLogisticaConnector;
use SmartDato\CblLogistica\Contracts\TokenStore;
use SmartDato\CblLogistica\Data\Credentials;
use SmartDato\CblLogistica\Exceptions\ValidationException;
use SmartDato\CblLogistica\Resources\PodResource;
use SmartDato\CblLogistica\Resources\ShipmentsResource;
use SmartDato\CblLogistica\Resources\StatusResource;

/**
 * The package entrypoint. It holds no account state until withCredentials() is
 * called, which returns a configured clone — so one container singleton can serve
 * any number of CBL accounts, including several accounts of the same carrier.
 */
class CblLogistica
{
    private ?ShipmentsResource $shipmentsResource = null;

    private ?StatusResource $statusResource = null;

    private ?PodResource $podResource = null;

    private ?CblLogisticaConnector $connector = null;

    public function __construct(
        protected ?Credentials $credentials = null,
        protected ?TokenStore $tokenStore = null,
    ) {}

    /**
     * Returns a clone bound to the given account. The clone starts with no cached
     * resources or connector, so nothing built for the previous account leaks into
     * it — see __clone().
     */
    public function withCredentials(Credentials $credentials): static
    {
        $clone = clone $this;
        $clone->credentials = $credentials;

        return $clone;
    }

    public function withTokenStore(TokenStore $tokenStore): static
    {
        $clone = clone $this;
        $clone->tokenStore = $tokenStore;

        return $clone;
    }

    /**
     * Everything account-derived is discarded on clone. Without this, a clone made
     * by withCredentials() would inherit a resource still wired to the previous
     * account's connector and authenticate as the wrong client.
     */
    public function __clone(): void
    {
        $this->shipmentsResource = null;
        $this->statusResource = null;
        $this->podResource = null;
        $this->connector = null;
    }

    public function shipments(): ShipmentsResource
    {
        return $this->shipmentsResource ??= new ShipmentsResource($this->connector(), $this->requireCredentials());
    }

    public function status(): StatusResource
    {
        return $this->statusResource ??= new StatusResource($this->connector());
    }

    public function pod(): PodResource
    {
        return $this->podResource ??= new PodResource($this->connector());
    }

    public function credentials(): ?Credentials
    {
        return $this->credentials;
    }

    /**
     * The daily token for this account, fetched and cached on first use. Callers
     * never need it — the authenticator adds it to every request body — but it is
     * exposed for diagnostics.
     */
    public function dailyToken(): string
    {
        return $this->authenticator()->dailyToken();
    }

    protected function connector(): CblLogisticaConnector
    {
        return $this->connector ??= new CblLogisticaConnector($this->authenticator());
    }

    protected function authenticator(): CblAuthenticator
    {
        return new CblAuthenticator($this->requireCredentials(), $this->tokenStore());
    }

    protected function tokenStore(): TokenStore
    {
        return $this->tokenStore ??= new ArrayTokenStore;
    }

    protected function requireCredentials(): Credentials
    {
        return $this->credentials
            ?? throw new ValidationException('Credentials are required, call withCredentials() first.');
    }
}
