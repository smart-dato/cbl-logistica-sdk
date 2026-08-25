<?php

namespace SmartDato\CblLogistica\Resources;

use SmartDato\CblLogistica\Connectors\CblLogisticaConnector;
use SmartDato\CblLogistica\Data\Credentials;
use SmartDato\CblLogistica\Data\Responses\ConfirmationResultData;
use SmartDato\CblLogistica\Data\Responses\DeletionResultData;
use SmartDato\CblLogistica\Data\Responses\PendingShipmentsData;
use SmartDato\CblLogistica\Data\Responses\ShipmentResultData;
use SmartDato\CblLogistica\Data\Shipments\ShipmentData;
use SmartDato\CblLogistica\Exceptions\ValidationException;
use SmartDato\CblLogistica\Requests\Shipments\ConfirmDayShipmentsRequest;
use SmartDato\CblLogistica\Requests\Shipments\CreateShipmentRequest;
use SmartDato\CblLogistica\Requests\Shipments\DeleteConfirmedShipmentsRequest;
use SmartDato\CblLogistica\Requests\Shipments\DeletePendingShipmentsRequest;
use SmartDato\CblLogistica\Requests\Shipments\DeleteShipmentPackagesRequest;
use SmartDato\CblLogistica\Requests\Shipments\GetPendingShipmentsRequest;
use SmartDato\CblLogistica\Requests\Shipments\PrintShipmentPackagesRequest;

/**
 * The clientCode is taken from the account's credentials rather than from caller
 * DTOs, so an application juggling several CBL accounts cannot send one account's
 * code under another's authentication.
 */
class ShipmentsResource extends BaseResource
{
    public function __construct(
        CblLogisticaConnector $connector,
        protected readonly Credentials $credentials,
    ) {
        parent::__construct($connector);
    }

    public function create(ShipmentData $data): ShipmentResultData
    {
        $this->ensureReferenceFits($data->clientReference);

        $response = $this->send(new CreateShipmentRequest($this->credentials->clientCode, $data));

        return ShipmentResultData::from($response->json());
    }

    /**
     * Hands registered references to CBL. Required only for accounts configured
     * for day confirmation, where create() alone leaves a shipment pending.
     *
     * @param  array<int, string>  $references
     */
    public function confirm(array $references): ConfirmationResultData
    {
        $this->ensureNotEmpty($references, 'confirm');
        array_walk($references, $this->ensureReferenceFits(...));

        $response = $this->send(new ConfirmDayShipmentsRequest($references));

        return ConfirmationResultData::from($response->json());
    }

    public function pending(): PendingShipmentsData
    {
        $response = $this->send(new GetPendingShipmentsRequest);

        return PendingShipmentsData::from($response->json());
    }

    /**
     * @param  array<int, string>  $references
     */
    public function deletePending(array $references): DeletionResultData
    {
        $this->ensureNotEmpty($references, 'deletePending');
        array_walk($references, $this->ensureReferenceFits(...));

        $response = $this->send(new DeletePendingShipmentsRequest($this->credentials->clientCode, $references));

        return DeletionResultData::from($response->json());
    }

    /**
     * @param  array<int, string>  $references
     */
    public function deleteConfirmed(array $references): DeletionResultData
    {
        $this->ensureNotEmpty($references, 'deleteConfirmed');
        array_walk($references, $this->ensureReferenceFits(...));

        $response = $this->send(new DeleteConfirmedShipmentsRequest($this->credentials->clientCode, $references));

        return DeletionResultData::from($response->json());
    }

    /**
     * @param  array<int, string>  $ssccs
     */
    public function deletePackages(array $ssccs): DeletionResultData
    {
        $this->ensureNotEmpty($ssccs, 'deletePackages');

        $response = $this->send(new DeleteShipmentPackagesRequest($ssccs));

        return DeletionResultData::from($response->json());
    }

    /**
     * Reprints labels for an existing reference. CBL answers with the same envelope
     * as create(), except carrierReference and clientReference come back null.
     *
     * @param  array<int, int>  $packageNumbers
     */
    public function reprint(string $reference, array $packageNumbers = []): ShipmentResultData
    {
        $this->ensureReferenceFits($reference);

        $response = $this->send(new PrintShipmentPackagesRequest(
            $this->credentials->clientCode,
            $reference,
            $packageNumbers,
        ));

        return ShipmentResultData::from($response->json());
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function ensureNotEmpty(array $values, string $method): void
    {
        if ($values === []) {
            throw new ValidationException("At least one value is required, {$method}() was called with an empty list.");
        }
    }

    /**
     * CBL truncates a client reference past 20 characters without saying so, which
     * silently merges two shipments whose references share a prefix. Refusing the
     * call is the only way the caller finds out.
     */
    private function ensureReferenceFits(string $reference): void
    {
        $limit = ShipmentData::MAX_CLIENT_REFERENCE_LENGTH;

        if (mb_strlen($reference) > $limit) {
            throw new ValidationException(
                "The client reference \"{$reference}\" is longer than the {$limit} characters CBL stores; "
                .'it would be truncated silently and could collide with another shipment.',
            );
        }
    }
}
