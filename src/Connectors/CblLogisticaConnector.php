<?php

namespace SmartDato\CblLogistica\Connectors;

use Saloon\Contracts\Authenticator;
use Saloon\Http\Connector;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use SmartDato\CblLogistica\Exceptions\CblLogisticaApiException;
use Throwable;

/**
 * Every CBL operation lives behind one host and one credential set, so a single
 * connector covers them all. One instance belongs to one account: it owns the
 * authenticator, which owns the credentials.
 *
 * The constructor property is not called $authenticator because Saloon's Connector
 * already declares one and PHP forbids redeclaring it as readonly.
 */
class CblLogisticaConnector extends Connector
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;

    public function __construct(
        protected readonly ?Authenticator $cblAuthenticator = null,
        protected readonly ?string $baseUrl = null,
    ) {}

    public function resolveBaseUrl(): string
    {
        return $this->baseUrl
            ?? (string) config('cbl-logistica-sdk.base_url', 'https://clientesws.cbl-logistica.com/api/v1.0');
    }

    protected function defaultAuth(): ?Authenticator
    {
        return $this->cblAuthenticator;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultConfig(): array
    {
        return [
            'timeout' => config('cbl-logistica-sdk.http.timeout', 30),
            'verify' => config('cbl-logistica-sdk.http.verify', true),
        ];
    }

    /**
     * Only transport and authentication failures land here. A rejected shipment
     * comes back as HTTP 200 with a populated errorList and is not an exception.
     */
    public function getRequestException(Response $response, ?Throwable $senderException): ?Throwable
    {
        return CblLogisticaApiException::fromResponse($response);
    }
}
