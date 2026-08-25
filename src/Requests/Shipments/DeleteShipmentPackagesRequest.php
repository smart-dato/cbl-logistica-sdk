<?php

namespace SmartDato\CblLogistica\Requests\Shipments;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Deletes individual packages by SSCC.
 *
 * The endpoint really is spelled "DeletShipmentPackages" — the typo is CBL's and
 * is part of the URL.
 */
class DeleteShipmentPackagesRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<int, string>  $packagesSSCC
     */
    public function __construct(
        protected readonly array $packagesSSCC,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/ShipmentRegistry/DeletShipmentPackages';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return ['packagesSSCC' => array_values($this->packagesSSCC)];
    }
}
