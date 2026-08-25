<?php

namespace SmartDato\CblLogistica\Auth\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * CBL documents this as a GET that carries a JSON body, and only accepts it that
 * way — the mirrored POST route behaves identically but is not the documented one.
 */
class GetDailyTokenRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::GET;

    public function __construct(
        protected readonly string $clientToken,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/TokenAuth/Get';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultBody(): array
    {
        return ['clientToken' => $this->clientToken];
    }
}
