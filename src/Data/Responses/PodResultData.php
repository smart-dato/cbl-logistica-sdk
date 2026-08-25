<?php

namespace SmartDato\CblLogistica\Data\Responses;

use SmartDato\CblLogistica\Data\Responses\Concerns\HasErrorEnvelope;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

final class PodResultData extends Data
{
    use HasErrorEnvelope;

    /**
     * @param  array<int, PodDocumentData>|null  $podList
     * @param  array<int, ErrorData>|null  $errorList
     * @param  array<int, WarningData>|null  $warningList
     */
    public function __construct(
        #[DataCollectionOf(PodDocumentData::class)]
        public ?array $podList = null,
        #[DataCollectionOf(ErrorData::class)]
        public ?array $errorList = null,
        #[DataCollectionOf(WarningData::class)]
        public ?array $warningList = null,
    ) {}

    /**
     * @return array<int, PodDocumentData>
     */
    public function documents(): array
    {
        return $this->podList ?? [];
    }

    public function first(): ?PodDocumentData
    {
        return $this->documents()[0] ?? null;
    }
}
