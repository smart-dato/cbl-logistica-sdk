<?php

namespace SmartDato\CblLogistica\Auth;

use Saloon\Http\Connector;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use SmartDato\CblLogistica\Exceptions\CblLogisticaApiException;
use Throwable;

/**
 * Serves the token endpoint alone. Keeping it apart from the main connector is what
 * stops CblAuthenticator from recursing: fetching a daily token must not itself
 * require a daily token.
 */
class TokenConnector extends Connector
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;

    public function resolveBaseUrl(): string
    {
        return (string) config('cbl-logistica-sdk.token_url', 'https://clientesws.cbl-logistica.com/api/v1.0');
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

    public function getRequestException(Response $response, ?Throwable $senderException): ?Throwable
    {
        return CblLogisticaApiException::fromResponse($response);
    }
}
