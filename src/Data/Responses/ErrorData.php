<?php

namespace SmartDato\CblLogistica\Data\Responses;

use Spatie\LaravelData\Data;

final class ErrorData extends Data
{
    public function __construct(
        public ?string $errorCode = null,
        public ?string $errorDescription = null,
    ) {}
}
