<?php

namespace SmartDato\CblLogistica\Data\Responses;

use Spatie\LaravelData\Data;

/**
 * One label from a CreateShipment response. The tag is raw ZPL; rendering it to
 * PDF or PNG is left to the caller.
 */
final class PackageTagData extends Data
{
    public function __construct(
        public ?int $packageNumber = null,
        public ?string $sscc = null,
        public ?string $tag = null,
    ) {}
}
