<?php

/**
 * Live calls against the CBL test account.
 *
 * These are the only place the undocumented response envelopes can be observed —
 * CBL publishes no schema for CreateShipment, ConfirmDayShipments or
 * PrintShipmentPackages. Run them to record or re-verify the fixtures:
 *
 *   CBL_LOGISTICA_INTEGRATION=1 vendor/bin/pest --group=integration
 *
 * They are gated twice on purpose: by group AND by env var. The default test run
 * excludes the group, so no accidental writes reach the carrier.
 */

use Carbon\CarbonImmutable;
use SmartDato\CblLogistica\Cache\ArrayTokenStore;
use SmartDato\CblLogistica\CblLogistica;
use SmartDato\CblLogistica\Data\Credentials;
use SmartDato\CblLogistica\Data\Shipments\AddressData;
use SmartDato\CblLogistica\Data\Shipments\PackageData;
use SmartDato\CblLogistica\Data\Shipments\ShipmentData;

pest()->group('integration');

uses()->beforeEach(function (): void {
    if (! env('CBL_LOGISTICA_INTEGRATION')) {
        $this->markTestSkipped('Set CBL_LOGISTICA_INTEGRATION=1 to run the live CBL tests.');
    }
})->in(__DIR__);

function liveCbl(): CblLogistica
{
    foreach (['CBL_LOGISTICA_USERNAME', 'CBL_LOGISTICA_PASSWORD', 'CBL_LOGISTICA_CLIENT_TOKEN', 'CBL_LOGISTICA_CLIENT_CODE'] as $key) {
        if (! env($key)) {
            test()->markTestSkipped("Set {$key} to run the live CBL tests.");
        }
    }

    return new CblLogistica(tokenStore: new ArrayTokenStore)->withCredentials(new Credentials(
        username: (string) env('CBL_LOGISTICA_USERNAME'),
        password: (string) env('CBL_LOGISTICA_PASSWORD'),
        clientToken: (string) env('CBL_LOGISTICA_CLIENT_TOKEN'),
        clientCode: (string) env('CBL_LOGISTICA_CLIENT_CODE'),
    ));
}

/**
 * Must stay within ShipmentData::MAX_CLIENT_REFERENCE_LENGTH — CBL truncates past
 * 20 characters without a word, which made two runs of this test collide into one
 * shipment before the limit was found.
 */
function liveReference(): string
{
    return 'SDK'.CarbonImmutable::now()->format('ymdHis').random_int(10, 99);
}

/**
 * @param  list<int>|null  $sendPackages  which of the declared packages to register
 *                                        on this call; null sends them all
 */
function liveShipment(string $reference, int $packages = 2, ?array $sendPackages = null): ShipmentData
{
    return new ShipmentData(
        clientReference: $reference,
        sender: new AddressData(
            name: 'OMEST SRL',
            street: 'Via L. Negrelli 15',
            postalCode: '39100',
            city: 'BOLZANO',
            country: 'IT',
            province: 'BZ',
        ),
        receiver: new AddressData(
            name: 'SDK Integration Test',
            street: 'Calle Mayor 1',
            postalCode: '08029',
            city: 'BARCELONA',
            country: 'ES',
            province: 'BARCELONA',
        ),
        numPackages: $packages,
        weight: 2.0,
        volume: 0.02,
        packages: array_map(
            static fn (int $number): PackageData => new PackageData(
                packageNumber: $number,
                width: 0.2,
                height: 0.2,
                depth: 0.2,
                weight: 1.0,
            ),
            $sendPackages ?? range(1, $packages),
        ),
        observations1: 'SDK integration test — please ignore',
        carrier: 'SALVAT',
    );
}

it('issues a daily token', function (): void {
    expect(liveCbl()->dailyToken())->toBeString()->not->toBeEmpty();
});

it('creates a shipment and returns ZPL plus an SSCC for every package', function (): void {
    $reference = liveReference();

    $result = liveCbl()->shipments()->create(liveShipment($reference));

    // Record whatever came back — the fixtures are derived from the manual, not the wire.
    file_put_contents(
        __DIR__.'/../Fixtures/responses/create-shipment-recorded.json',
        json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );

    expect($result->errorMessages())->toBe([])
        ->and($result->succeeded())->toBeTrue()
        ->and($result->labels())->toHaveCount(2);

    foreach ($result->labels() as $zpl) {
        expect($zpl)->toContain('^XA');
    }

    foreach ($result->ssccs() as $sscc) {
        expect($sscc)->toHaveLength(20);
    }

    liveCbl()->shipments()->deletePending([$reference]);
});

it('lists the new shipment as pending and confirms it', function (): void {
    $cbl = liveCbl();
    $reference = liveReference();

    $cbl->shipments()->create(liveShipment($reference));

    expect($cbl->shipments()->pending()->findByClientReference($reference))->not->toBeNull();

    $confirmation = $cbl->shipments()->confirm([$reference]);

    expect($confirmation->errorMessages())->toBe([])
        ->and($confirmation->generatedShipments)->toBeGreaterThanOrEqual(1)
        ->and($cbl->shipments()->pending()->findByClientReference($reference))->toBeNull();
});

it('deletes a pending shipment again', function (): void {
    $cbl = liveCbl();
    $reference = liveReference();

    $cbl->shipments()->create(liveShipment($reference));
    $cbl->shipments()->deletePending([$reference]);

    expect($cbl->shipments()->pending()->findByClientReference($reference))->toBeNull();
});

it('registers packages over several calls under one reference', function (): void {
    $cbl = liveCbl();
    $reference = liveReference();

    // Declare three packages up front, then register them across two calls.
    $first = $cbl->shipments()->create(liveShipment($reference, packages: 3, sendPackages: [1]));
    $second = $cbl->shipments()->create(liveShipment($reference, packages: 3, sendPackages: [2, 3]));

    expect($first->errorMessages())->toBe([])
        ->and($second->errorMessages())->toBe([])
        // Both calls belong to the same CBL shipment...
        ->and($second->carrierReference)->toBe($first->carrierReference)
        // ...and each returns only the packages it registered.
        ->and(array_keys($first->labels()))->toBe([1])
        ->and(array_keys($second->labels()))->toBe([2, 3]);

    // Complete package count, so the shipment is closed and awaiting confirmation.
    expect($cbl->shipments()->pending()->findByClientReference($reference)?->isClosed())->toBeTrue();

    // Removing a package by SSCC drops it back to pending.
    $sscc = $second->ssccs()[3];
    expect($cbl->shipments()->deletePackages([$sscc])->deletedPackages)->toBe(1)
        ->and($cbl->shipments()->pending()->findByClientReference($reference)?->isClosed())->toBeFalse();

    $cbl->shipments()->deletePending([$reference]);
});

it('treats an unknown SSCC as a silent no-op rather than an error', function (): void {
    $result = liveCbl()->shipments()->deletePackages(['00000000000000000000']);

    expect($result->deletedPackages)->toBe(0)
        ->and($result->errorMessages())->toBe([]);
});

it('reprints the label of an existing package', function (): void {
    $cbl = liveCbl();
    $reference = liveReference();

    $created = $cbl->shipments()->create(liveShipment($reference));
    $reprinted = $cbl->shipments()->reprint($reference, [1]);

    expect($reprinted->errorMessages())->toBe([])
        ->and($reprinted->labels())->toHaveCount(1)
        ->and($reprinted->ssccs()[1])->toBe($created->ssccs()[1]);

    $cbl->shipments()->deletePending([$reference]);
});

it('reads status by reference', function (): void {
    $result = liveCbl()->status()->byReference((string) env('CBL_LOGISTICA_KNOWN_REFERENCE', '999935360776'));

    expect($result->errorMessages())->toBe([]);
});

it('reads proof of delivery by reference', function (): void {
    $result = liveCbl()->pod()->byReference((string) env('CBL_LOGISTICA_KNOWN_REFERENCE', '104932869'));

    expect($result->errorMessages())->toBe([]);
});

it('reads status over a date range', function (): void {
    $result = liveCbl()->status()->byDateRange(
        CarbonImmutable::now()->subDays(10),
        CarbonImmutable::now(),
    );

    expect($result->errorMessages())->toBe([]);
});
