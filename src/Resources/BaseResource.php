<?php

namespace SmartDato\CblLogistica\Resources;

use Saloon\Http\Request;
use Saloon\Http\Response;
use SmartDato\CblLogistica\Connectors\CblLogisticaConnector;

/**
 * Retains the last request and response so callers can journal the exact bytes
 * that crossed the wire — OLC records every carrier call in its api_calls table.
 */
abstract class BaseResource
{
    protected ?Response $lastResponse = null;

    public function __construct(
        protected readonly CblLogisticaConnector $connector,
    ) {}

    public function lastResponse(): ?Response
    {
        return $this->lastResponse;
    }

    public function lastRawRequest(): ?string
    {
        $body = $this->lastResponse?->getPendingRequest()->body();

        if ($body === null) {
            return null;
        }

        $value = $body->all();

        return is_string($value) ? $value : (json_encode($value) ?: null);
    }

    public function lastRawResponse(): ?string
    {
        return $this->lastResponse?->body();
    }

    protected function send(Request $request): Response
    {
        return $this->lastResponse = $this->connector->send($request);
    }
}
