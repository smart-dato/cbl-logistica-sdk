<?php

namespace SmartDato\CblLogistica\Requests\Shipments;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use SmartDato\CblLogistica\Data\Shipments\ShipmentData;
use SmartDato\CblLogistica\Support\StripsNullValues;

/**
 * Creates a shipment, or adds packages to an existing one when the same
 * clientReference is sent again.
 */
class CreateShipmentRequest extends Request implements HasBody
{
    use HasJsonBody;
    use StripsNullValues;

    protected Method $method = Method::POST;

    public function __construct(
        protected readonly string $clientCode,
        protected readonly ShipmentData $data,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/ShipmentRegistry/CreateShipment';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->withoutNullValues([
            'clientCode' => $this->clientCode,
            ...$this->data->toArray(),
        ]);
    }
}
