<?php

namespace SmartDato\CblLogistica\Requests\Shipments;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Hands registered shipments to CBL for processing. Only accounts configured for
 * day confirmation need this, and it confirms only the calling account's own
 * references — it takes no clientCode.
 */
class ConfirmDayShipmentsRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<int, string>  $shipmentReferences
     */
    public function __construct(
        protected readonly array $shipmentReferences,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/ShipmentRegistry/ConfirmDayShipments';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return ['shipmentReferences' => array_values($this->shipmentReferences)];
    }
}
