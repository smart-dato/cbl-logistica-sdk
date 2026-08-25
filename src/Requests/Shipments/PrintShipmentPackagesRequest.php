<?php

namespace SmartDato\CblLogistica\Requests\Shipments;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Reprints labels for selected packages of an existing reference.
 */
class PrintShipmentPackagesRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<int, int>  $packageNumbers
     */
    public function __construct(
        protected readonly string $clientCode,
        protected readonly string $reference,
        protected readonly array $packageNumbers = [],
    ) {}

    public function resolveEndpoint(): string
    {
        return '/ShipmentRegistry/PrintShipmentPackages';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'clientCode' => $this->clientCode,
            'reference' => $this->reference,
            'packageNumbers' => array_values($this->packageNumbers),
        ];
    }
}
