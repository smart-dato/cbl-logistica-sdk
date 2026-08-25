<?php

namespace SmartDato\CblLogistica\Requests\Shipments;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * CBL serves this over both GET and POST; POST is used here because the payload
 * is a body either way.
 */
class GetPendingShipmentsRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function resolveEndpoint(): string
    {
        return '/ShipmentRegistry/GetPendingShipments';
    }

    /**
     * The dailyToken is added by the authenticator; there is nothing else to send.
     *
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [];
    }
}
