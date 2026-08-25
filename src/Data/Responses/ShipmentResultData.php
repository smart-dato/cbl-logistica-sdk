<?php

namespace SmartDato\CblLogistica\Data\Responses;

use SmartDato\CblLogistica\Data\Responses\Concerns\HasErrorEnvelope;
use SmartDato\CblLogistica\Enums\ResponseStatus;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

/**
 * A CreateShipment response, and also a PrintShipmentPackages one — reprinting
 * returns the identical envelope, with carrierReference and clientReference null.
 *
 * On an account with day-confirmation enabled the shipment is registered but not
 * yet handed to CBL — ConfirmDayShipments does that.
 */
final class ShipmentResultData extends Data
{
    use HasErrorEnvelope;

    /**
     * @param  array<int, ErrorData>|null  $errorList
     * @param  array<int, WarningData>|null  $warningList
     * @param  array<int, PackageTagData>|null  $packagesTags
     */
    public function __construct(
        public ?string $carrierReference = null,
        public ?string $clientReference = null,
        public ?ResponseStatus $status = null,
        #[DataCollectionOf(ErrorData::class)]
        public ?array $errorList = null,
        #[DataCollectionOf(WarningData::class)]
        public ?array $warningList = null,
        #[DataCollectionOf(PackageTagData::class)]
        public ?array $packagesTags = null,
    ) {}

    public function succeeded(): bool
    {
        if ($this->status !== null) {
            return $this->status === ResponseStatus::Ok;
        }

        return ! $this->hasErrors();
    }

    /**
     * @return array<int, PackageTagData>
     */
    public function packages(): array
    {
        return $this->packagesTags ?? [];
    }

    /**
     * Raw ZPL keyed by package number.
     *
     * @return array<int, string>
     */
    public function labels(): array
    {
        $labels = [];

        foreach ($this->packages() as $package) {
            if ($package->packageNumber !== null && $package->tag !== null) {
                $labels[$package->packageNumber] = $package->tag;
            }
        }

        return $labels;
    }

    /**
     * SSCC keyed by package number.
     *
     * @return array<int, string>
     */
    public function ssccs(): array
    {
        $ssccs = [];

        foreach ($this->packages() as $package) {
            if ($package->packageNumber !== null && $package->sscc !== null) {
                $ssccs[$package->packageNumber] = $package->sscc;
            }
        }

        return $ssccs;
    }
}
