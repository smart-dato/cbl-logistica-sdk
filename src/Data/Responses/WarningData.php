<?php

namespace SmartDato\CblLogistica\Data\Responses;

use Spatie\LaravelData\Data;

/**
 * Codes seen in the wild: 105 (a magnitude exceeds its maximum) and 300 (the
 * requested date range was wider than the endpoint allows and got clamped).
 */
final class WarningData extends Data
{
    public function __construct(
        public ?string $warningCode = null,
        public ?string $warningDescription = null,
    ) {}
}
