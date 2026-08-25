<?php

namespace SmartDato\CblLogistica\Data\Responses;

use Carbon\CarbonImmutable;
use SmartDato\CblLogistica\Support\CblDateFormats;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

final class StatusEventData extends Data
{
    public function __construct(
        public ?string $carrierNumber = null,
        public ?string $clientReference = null,
        #[WithCast(DateTimeInterfaceCast::class, format: CblDateFormats::DATETIME, type: CarbonImmutable::class)]
        public ?CarbonImmutable $statusDate = null,
        public ?string $statusCode = null,
        public ?string $statusDescription = null,
        public ?string $statusObservations = null,
    ) {}
}
