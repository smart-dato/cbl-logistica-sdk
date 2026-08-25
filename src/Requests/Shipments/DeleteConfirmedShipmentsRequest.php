<?php

namespace SmartDato\CblLogistica\Requests\Shipments;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Marks already-confirmed references as deleted. CBL cannot remove them
 * internally — the branch office has to be contacted to finish the job.
 */
class DeleteConfirmedShipmentsRequest extends Request implements HasBody
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
        return '/ShipmentRegistry/DeleteConfirmedShipments';
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
