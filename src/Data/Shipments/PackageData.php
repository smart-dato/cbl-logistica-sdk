<?php

namespace SmartDato\CblLogistica\Data\Shipments;

use Spatie\LaravelData\Data;

/**
 * One package of a shipment. Only packageNumber is mandatory — CBL generates an
 * SSCC per package when none is supplied, and derives shipment totals from the
 * package measurements when the header omits them.
 *
 * Measurements are metres (max 999.99) and kilograms (max 999999); exceeding
 * either yields warning 105 rather than an error.
 */
final class PackageData extends Data
{
    public function __construct(
        public int $packageNumber,
        public ?string $sscc = null,
        public ?float $width = null,
        public ?float $height = null,
        public ?float $depth = null,
        public ?float $weight = null,
        public ?string $packageType = null,
        public ?string $description = null,
    ) {}
}
