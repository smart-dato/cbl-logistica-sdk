<?php

namespace SmartDato\CblLogistica\Data\Responses;

use SmartDato\CblLogistica\Data\Responses\Concerns\HasErrorEnvelope;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

/**
 * The shape the three delete endpoints answer with. DeletePendingShipments and
 * DeleteConfirmedShipments report deletedShipments; DeleteShipmentPackages was
 * observed reporting nothing but the envelope.
 */
final class DeletionResultData extends Data
{
    use HasErrorEnvelope;

    /**
     * @param  array<int, ErrorData>|null  $errorList
     * @param  array<int, WarningData>|null  $warningList
     */
    public function __construct(
        public ?int $deletedShipments = null,
        public ?int $deletedPackages = null,
        #[DataCollectionOf(ErrorData::class)]
        public ?array $errorList = null,
        #[DataCollectionOf(WarningData::class)]
        public ?array $warningList = null,
    ) {}
}
