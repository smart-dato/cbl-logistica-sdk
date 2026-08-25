<?php

namespace SmartDato\CblLogistica\Data\Shipments;

use DateTimeInterface;
use SmartDato\CblLogistica\Enums\Freight;
use SmartDato\CblLogistica\Support\CblDateFormats;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;

/**
 * A CreateShipment payload. dailyToken and clientCode are absent on purpose: the
 * authenticator injects the token and the resource injects the account's client
 * code, so neither is the caller's concern.
 *
 * Packages must be numbered. If fewer packages arrive than numPackages declares,
 * an account with day-confirmation enabled leaves the shipment pending until the
 * rest are registered, while an account without it gets a package-count error.
 */
final class ShipmentData extends Data
{
    /**
     * CBL stores at most 20 characters of a clientReference. It does not say so
     * anywhere, and it does not complain: a longer value is silently truncated and
     * still answers status OK, so two references sharing a 20-character prefix
     * collapse into one shipment. ShipmentsResource refuses to send one rather
     * than let that happen quietly.
     */
    public const int MAX_CLIENT_REFERENCE_LENGTH = 20;

    /**
     * @param  array<int, PackageData>  $packages
     */
    public function __construct(
        public string $clientReference,
        public AddressData $sender,
        public AddressData $receiver,
        public int $numPackages,
        public float $weight,
        public array $packages = [],
        public ?float $volume = null,
        public ?string $carrierReference = null,
        public ?float $cashOnDelivery = null,
        public ?string $observations1 = null,
        public ?string $observations2 = null,
        /**
         * Sent without a UTC offset on purpose. This is a delivery date, so CBL has
         * to read it in its own timezone — laravel-data's default ISO-8601 output
         * would append an offset and could move the delivery to the previous day.
         */
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: CblDateFormats::OUTGOING)]
        public ?DateTimeInterface $postponedDate = null,
        public ?Freight $freight = null,
        public ?string $serviceType = null,
        /** Must be "SALVAT" for international shipments. */
        public ?string $carrier = null,
        public ?string $pickupObservations1 = null,
        public ?string $pickupObservations2 = null,
        public ?string $serviceTypePickupExp = null,
    ) {}
}
