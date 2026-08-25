<?php

namespace SmartDato\CblLogistica\Data\Responses;

use SmartDato\CblLogistica\Data\Responses\Concerns\HasErrorEnvelope;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

final class StatusResultData extends Data
{
    use HasErrorEnvelope;

    /**
     * @param  array<int, StatusEventData>|null  $statusList
     * @param  array<int, ErrorData>|null  $errorList
     * @param  array<int, WarningData>|null  $warningList
     */
    public function __construct(
        #[DataCollectionOf(StatusEventData::class)]
        public ?array $statusList = null,
        #[DataCollectionOf(ErrorData::class)]
        public ?array $errorList = null,
        #[DataCollectionOf(WarningData::class)]
        public ?array $warningList = null,
    ) {}

    /**
     * @return array<int, StatusEventData>
     */
    public function events(): array
    {
        return $this->statusList ?? [];
    }
}
