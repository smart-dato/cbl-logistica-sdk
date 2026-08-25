<?php

namespace SmartDato\CblLogistica\Data\Responses;

use Carbon\CarbonImmutable;
use SmartDato\CblLogistica\Support\CblDateFormats;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

/**
 * One proof-of-delivery document. binaryFile is base64; decoded() hands back the
 * raw bytes ready to be written to disk.
 */
final class PodDocumentData extends Data
{
    public function __construct(
        public ?string $carrierNumber = null,
        public ?string $clientReference = null,
        public ?string $binaryFile = null,
        #[WithCast(DateTimeInterfaceCast::class, format: CblDateFormats::DATETIME, type: CarbonImmutable::class)]
        public ?CarbonImmutable $uploadDate = null,
        public ?string $extension = null,
        public ?string $imageType = null,
    ) {}

    public function decoded(): ?string
    {
        if ($this->binaryFile === null || $this->binaryFile === '') {
            return null;
        }

        $decoded = base64_decode($this->binaryFile, true);

        return $decoded === false ? null : $decoded;
    }

    /**
     * A lower-case file extension without the leading dot, e.g. "pdf".
     */
    public function fileExtension(): ?string
    {
        return $this->extension === null ? null : mb_strtolower(ltrim($this->extension, '.'));
    }
}
