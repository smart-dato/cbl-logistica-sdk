<?php

namespace SmartDato\CblLogistica\Data\Responses;

use SmartDato\CblLogistica\Data\Responses\Concerns\HasErrorEnvelope;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

final class PendingShipmentsData extends Data
{
    use HasErrorEnvelope;

    /**
     * @param  array<int, PendingShipmentData>|null  $pendingShipments
     * @param  array<int, ErrorData>|null  $errorList
     * @param  array<int, WarningData>|null  $warningList
     */
    public function __construct(
        #[DataCollectionOf(PendingShipmentData::class)]
        public ?array $pendingShipments = null,
        #[DataCollectionOf(ErrorData::class)]
        public ?array $errorList = null,
        #[DataCollectionOf(WarningData::class)]
        public ?array $warningList = null,
    ) {}

    /**
     * @return array<int, PendingShipmentData>
     */
    public function shipments(): array
    {
        return $this->pendingShipments ?? [];
    }

    /**
     * The shipments whose declared package count is complete and which are waiting
     * for ConfirmDayShipments.
     *
     * @return array<int, PendingShipmentData>
     */
    public function closed(): array
    {
        return array_values(array_filter(
            $this->shipments(),
            static fn (PendingShipmentData $shipment): bool => $shipment->isClosed(),
        ));
    }

    public function findByClientReference(string $clientReference): ?PendingShipmentData
    {
        foreach ($this->shipments() as $shipment) {
            if ($shipment->clientReference === $clientReference) {
                return $shipment;
            }
        }

        return null;
    }
}
