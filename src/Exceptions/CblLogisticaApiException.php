<?php

namespace SmartDato\CblLogistica\Exceptions;

use Saloon\Http\Response;
use Throwable;

/**
 * An HTTP-level failure. Business failures are NOT exceptions — CBL answers 200
 * with a populated errorList, which the response DTOs expose instead.
 *
 * @phpstan-consistent-constructor Subclasses keep this signature so fromResponse()
 * returns the subclass rather than the base, which is the bug that makes
 * inpost-sdk's documented catch blocks unreachable.
 */
class CblLogisticaApiException extends CblLogisticaException
{
    public function __construct(
        string $message,
        int $code = 0,
        public readonly ?Response $response = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromResponse(Response $response): static
    {
        $status = $response->status();

        return new static(
            message: self::describe($response, $status),
            code: $status,
            response: $response,
        );
    }

    /**
     * CBL answers an authentication failure with a bare status line and an empty
     * body, so the payload can never be assumed to be JSON.
     */
    private static function describe(Response $response, int $status): string
    {
        $body = trim($response->body());

        if ($body === '') {
            return $status === 401
                ? 'CBL rejected the credentials (HTTP 401). Check the username, password, client token and that the daily token has not expired.'
                : "CBL returned HTTP {$status} with an empty body.";
        }

        try {
            $payload = $response->json();
        } catch (Throwable) {
            $payload = null;
        }

        if (is_array($payload)) {
            $detail = $payload['Message'] ?? $payload['message'] ?? $payload['ExceptionMessage'] ?? null;

            if (is_string($detail) && $detail !== '') {
                return "CBL returned HTTP {$status}: {$detail}";
            }
        }

        return "CBL returned HTTP {$status}: ".mb_substr($body, 0, 500);
    }
}
