<?php

namespace SmartDato\CblLogistica\Data\Responses;

use SmartDato\CblLogistica\Data\Responses\Concerns\HasErrorEnvelope;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

final class ConfirmationResultData extends Data
{
    use HasErrorEnvelope;

    /**
     * @param  array<int, ErrorData>|null  $errorList
     * @param  array<int, WarningData>|null  $warningList
     */
    public function __construct(
        public ?int $generatedShipments = null,
        #[DataCollectionOf(ErrorData::class)]
        public ?array $errorList = null,
        #[DataCollectionOf(WarningData::class)]
        public ?array $warningList = null,
    ) {}
}
