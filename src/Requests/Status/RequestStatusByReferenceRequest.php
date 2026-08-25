<?php

namespace SmartDato\CblLogistica\Requests\Status;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * The reference may be either a client reference or a CBL carrier number.
 */
class RequestStatusByReferenceRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected readonly string $reference,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/ShipmentStatus/RequestStatusByReference';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return ['reference' => $this->reference];
    }
}
