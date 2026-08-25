<?php

namespace SmartDato\CblLogistica\Requests\Shipments;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Deletes references that have not been confirmed yet.
 */
class DeletePendingShipmentsRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<int, string>  $shipmentReferences
     */
    public function __construct(
        protected readonly string $clientCode,
        protected readonly array $shipmentReferences,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/ShipmentRegistry/DeletePendingShipments';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'clientCode' => $this->clientCode,
            'shipmentReferences' => array_values($this->shipmentReferences),
        ];
    }
}
