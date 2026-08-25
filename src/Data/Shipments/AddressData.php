<?php

namespace SmartDato\CblLogistica\Data\Shipments;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

/**
 * A sender or receiver block. Name, street, postalCode, city and country are the
 * mandatory fields per mandatoryFields.txt; everything else may be omitted.
 */
final class AddressData extends Data
{
    public function __construct(
        public string $name,
        public string $street,
        public string $postalCode,
        public string $city,
        public string $country,
        public ?string $province = null,
        public ?string $phone = null,
        public ?string $contactPerson = null,
        /** CBL spells this field in caps; laravel-data would emit "nIF" without the mapping. */
        #[MapOutputName('NIF')]
        public ?string $nif = null,
        public ?string $email = null,
        public ?ScheduleData $schedule = null,
    ) {}
}
