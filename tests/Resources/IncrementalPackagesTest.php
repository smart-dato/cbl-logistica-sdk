<?php

/**
 * The "half way" flow the carrier ships sample files for: a shipment declares its
 * total package count up front, then packages are registered over several calls
 * that reuse one clientReference.
 */

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SmartDato\CblLogistica\Data\Responses\PendingShipmentsData;
use SmartDato\CblLogistica\Data\Shipments\PackageData;
use SmartDato\CblLogistica\Enums\PendingShipmentStatus;
use SmartDato\CblLogistica\Tests\Fixtures\CblFixtures;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

test('a later call reusing the reference registers the remaining packages', function (): void {
    $client = cblClient([
        MockResponse::make(CblFixtures::response('create-shipment-ok')),
        MockResponse::make(CblFixtures::response('create-shipment-partial')),
    ]);

    $first = $client->shipments()->create(CblFixtures::shipmentData([
        'numPackages' => 3,
        'weight' => 3.0,
        'packages' => [new PackageData(packageNumber: 1, weight: 1.0)],
    ]));

    $second = $client->shipments()->create(CblFixtures::shipmentData([
        'numPackages' => 3,
        'weight' => 3.0,
        'packages' => [
            new PackageData(packageNumber: 2, weight: 1.0),
            new PackageData(packageNumber: 3, weight: 1.0),
        ],
    ]));

    // The declared total is repeated on every call; only the new packages are sent.
    expect(lastSentBody()['numPackages'])->toBe(3)
        ->and(array_column(lastSentBody()['packages'], 'packageNumber'))->toBe([2, 3]);

    // CBL answers each call with only the packages that call registered.
    expect($first->succeeded())->toBeTrue()
        ->and($second->succeeded())->toBeTrue()
        ->and(array_keys($second->labels()))->toBe([2, 3])
        ->and($second->ssccs())->toBe([
            2 => '00000000000000254861',
            3 => '00000000000000254878',
        ]);
});

test('a shipment still missing packages reports itself as pending, not closed', function (): void {
    $result = cblClient([MockResponse::make(CblFixtures::response('pending-shipments-incomplete'))])
        ->shipments()
        ->pending();

    $shipment = $result->findByClientReference('HALFWAY140814');

    expect($result)->toBeInstanceOf(PendingShipmentsData::class)
        ->and($shipment?->status)->toBe(PendingShipmentStatus::Pending)
        ->and($shipment?->isClosed())->toBeFalse()
        ->and($shipment?->numPackages)->toBe(3)
        ->and($result->closed())->toBe([]);
});

test('deleting packages reports how many went, and an unknown SSCC is a silent zero', function (): void {
    $client = cblClient([
        MockResponse::make(CblFixtures::response('delete-shipment-packages')),
        MockResponse::make(['deletedPackages' => 0, 'errorList' => [], 'warningList' => []]),
    ]);

    $deleted = $client->shipments()->deletePackages(['00000000000000254878']);
    $missing = $client->shipments()->deletePackages(['00000000000000000000']);

    expect($deleted->deletedPackages)->toBe(1)
        ->and($deleted->hasErrors())->toBeFalse()
        // CBL does not complain about an SSCC it has never seen — only the count says so.
        ->and($missing->deletedPackages)->toBe(0)
        ->and($missing->hasErrors())->toBeFalse();
});
