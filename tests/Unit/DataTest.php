<?php

use Carbon\CarbonImmutable;
use SmartDato\CblLogistica\Data\Responses\PendingShipmentData;
use SmartDato\CblLogistica\Data\Responses\ShipmentResultData;
use SmartDato\CblLogistica\Data\Shipments\PackageData;
use SmartDato\CblLogistica\Enums\Freight;
use SmartDato\CblLogistica\Enums\PendingShipmentStatus;
use SmartDato\CblLogistica\Enums\ResponseStatus;
use SmartDato\CblLogistica\Tests\Fixtures\CblFixtures;

test('the NIF field keeps the carrier capitalisation on output', function (): void {
    $payload = CblFixtures::receiver()->toArray();

    expect($payload)->toHaveKey('NIF')
        ->and($payload['NIF'])->toBe('123456789B')
        ->and($payload)->not->toHaveKey('nif');
});

test('the shipment payload nests addresses and packages the way CBL expects', function (): void {
    $payload = CblFixtures::shipmentData()->toArray();

    expect($payload['clientReference'])->toBe('REFERENCE01')
        ->and($payload['sender']['postalCode'])->toBe('39100')
        ->and($payload['receiver']['city'])->toBe('BARCELONA')
        ->and($payload['packages'])->toHaveCount(2)
        ->and($payload['packages'][0]['packageNumber'])->toBe(1)
        ->and($payload['packages'][1]['packageNumber'])->toBe(2);
});

test('freight is serialised as its carrier code', function (): void {
    $payload = CblFixtures::shipmentData(['freight' => Freight::Pagado])->toArray();

    expect($payload['freight'])->toBe('P');
});

test('pending shipments coerce the string numbers and d/m/Y dates CBL sends', function (): void {
    $shipment = PendingShipmentData::from(CblFixtures::response('pending-shipments')['pendingShipments'][0]);

    expect($shipment->numPackages)->toBe(2)
        ->and($shipment->weight)->toBe(1000.0)
        ->and($shipment->createDate)->toBeInstanceOf(CarbonImmutable::class)
        ->and($shipment->createDate->toDateString())->toBe('2026-08-21')
        ->and($shipment->postponedDate)->toBeNull()
        ->and($shipment->status)->toBe(PendingShipmentStatus::Closed)
        ->and($shipment->isClosed())->toBeTrue();
});

test('a successful create result exposes the ZPL and SSCC per package', function (): void {
    $result = ShipmentResultData::from(CblFixtures::response('create-shipment-ok'));

    expect($result->status)->toBe(ResponseStatus::Ok)
        ->and($result->succeeded())->toBeTrue()
        ->and($result->hasErrors())->toBeFalse()
        ->and($result->carrierReference)->toBe('12527')
        ->and($result->ssccs())->toBe([
            1 => '00000000000000254823',
            2 => '00000000000000254830',
        ]);

    // CBL numbers the SSCC per package and every label is real ZPL.
    foreach ($result->labels() as $packageNumber => $zpl) {
        expect($packageNumber)->toBeIn([1, 2])
            ->and($zpl)->toStartWith('^XA')
            ->and($zpl)->toEndWith('^XZ');
    }

    expect($result->labels())->toHaveCount(2);
});

test('a rejected create result reports errors and warnings instead of throwing', function (): void {
    $result = ShipmentResultData::from(CblFixtures::response('create-shipment-error'));

    expect($result->status)->toBe(ResponseStatus::Error)
        ->and($result->succeeded())->toBeFalse()
        ->and($result->hasErrors())->toBeTrue()
        ->and($result->errorMessages())->toBe(['Wrong Postal Code'])
        ->and($result->hasWarnings())->toBeTrue()
        ->and($result->warningMessages())->toBe(['Magnitude weight in field package 1 is too big'])
        ->and($result->labels())->toBe([]);
});

test('a package with no measurements drops its null keys from the payload', function (): void {
    $payload = PackageData::from(['packageNumber' => 7])->toArray();

    expect($payload['packageNumber'])->toBe(7)
        ->and($payload['width'])->toBeNull();
});
