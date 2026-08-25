<?php

namespace SmartDato\CblLogistica\Data\Shipments;

use Spatie\LaravelData\Data;

/**
 * The delivery time windows of an address block. CBL accepts free-form strings
 * here and the samples send empty ones when there is no restriction.
 */
final class ScheduleData extends Data
{
    public function __construct(
        public ?string $morning = null,
        public ?string $evening = null,
    ) {}
}
