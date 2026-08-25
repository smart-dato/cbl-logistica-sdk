<?php

namespace SmartDato\CblLogistica\Data\Responses;

use Carbon\CarbonImmutable;
use SmartDato\CblLogistica\Enums\PendingShipmentStatus;
use SmartDato\CblLogistica\Support\CblDateFormats;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

/**
 * One GetPendingShipments entry. CBL returns numPackages and weight as strings
 * and createDate as d/m/Y here — unlike everywhere else in the API — so both are
 * coerced back to sensible PHP types.
 */
final class PendingShipmentData extends Data
{
    public function __construct(
        public ?string $clientCode = null,
        public ?string $clientReference = null,
        public ?string $carrierReference = null,
        public ?int $numPackages = null,
        public ?float $weight = null,
        #[WithCast(DateTimeInterfaceCast::class, format: CblDateFormats::DATE, type: CarbonImmutable::class)]
        public ?CarbonImmutable $createDate = null,
        #[WithCast(DateTimeInterfaceCast::class, format: CblDateFormats::DATE, type: CarbonImmutable::class)]
        public ?CarbonImmutable $postponedDate = null,
        public ?PendingShipmentStatus $status = null,
    ) {}

    public function isClosed(): bool
    {
        return $this->status === PendingShipmentStatus::Closed;
    }
}
