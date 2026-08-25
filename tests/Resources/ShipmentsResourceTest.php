<?php

use Carbon\CarbonImmutable;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SmartDato\CblLogistica\Data\Responses\ConfirmationResultData;
use SmartDato\CblLogistica\Data\Responses\PendingShipmentsData;
use SmartDato\CblLogistica\Data\Responses\ShipmentResultData;
use SmartDato\CblLogistica\Data\Shipments\PackageData;
use SmartDato\CblLogistica\Enums\ResponseStatus;
use SmartDato\CblLogistica\Exceptions\ValidationException;
use SmartDato\CblLogistica\Tests\Fixtures\CblFixtures;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

test('creating a shipment sends the account client code and returns the labels', function (): void {
    $result = cblClient([MockResponse::make(CblFixtures::response('create-shipment-ok'))])
        ->shipments()
        ->create(CblFixtures::shipmentData());

    $body = lastSentBody();

    expect($result)->toBeInstanceOf(ShipmentResultData::class)
        ->and($result->succeeded())->toBeTrue()
        ->and($result->labels())->toHaveCount(2)
        ->and($body['clientCode'])->toBe('000000311')
        ->and($body['clientReference'])->toBe('REFERENCE01')
        ->and($body['numPackages'])->toBe(2)
        ->and($body['sender']['NIF'])->toBe('01234567890')
        ->and($body)->toHaveKey('dailyToken');
});

test('null optionals are stripped from the outgoing payload rather than sent as null', function (): void {
    cblClient([MockResponse::make(CblFixtures::response('create-shipment-ok'))])
        ->shipments()
        ->create(CblFixtures::shipmentData([
            'volume' => null,
            'observations1' => null,
            'packages' => [new PackageData(packageNumber: 1)],
        ]));

    $body = lastSentBody();

    expect($body)->not->toHaveKey('volume')
        ->and($body)->not->toHaveKey('observations1')
        ->and($body)->not->toHaveKey('cashOnDelivery')
        ->and($body['packages'][0])->toBe(['packageNumber' => 1])
        ->and($body['receiver'])->not->toHaveKey('schedule');
});

test('a postponed date is sent in the carrier datetime format', function (): void {
    cblClient([MockResponse::make(CblFixtures::response('create-shipment-ok'))])
        ->shipments()
        ->create(CblFixtures::shipmentData([
            'postponedDate' => CarbonImmutable::parse('2026-09-01 08:30:00'),
        ]));

    expect(lastSentBody()['postponedDate'])->toBe('2026-09-01T08:30:00');
});

test('an international shipment carries the SALVAT carrier flag', function (): void {
    cblClient([MockResponse::make(CblFixtures::response('create-shipment-ok'))])
        ->shipments()
        ->create(CblFixtures::shipmentData(['carrier' => 'SALVAT']));

    expect(lastSentBody()['carrier'])->toBe('SALVAT');
});

test('a rejected shipment comes back as a result, not an exception', function (): void {
    $result = cblClient([MockResponse::make(CblFixtures::response('create-shipment-error'))])
        ->shipments()
        ->create(CblFixtures::shipmentData());

    expect($result->status)->toBe(ResponseStatus::Error)
        ->and($result->succeeded())->toBeFalse()
        ->and($result->errorMessages())->toBe(['Wrong Postal Code']);
});

test('confirming a day sends only the references and takes no client code', function (): void {
    $result = cblClient([MockResponse::make(CblFixtures::response('confirm-day-shipments'))])
        ->shipments()
        ->confirm(['REFERENCE01', 'REFERENCE02']);

    $body = lastSentBody();

    expect($result)->toBeInstanceOf(ConfirmationResultData::class)
        ->and($result->generatedShipments)->toBe(2)
        ->and($body['shipmentReferences'])->toBe(['REFERENCE01', 'REFERENCE02'])
        ->and($body)->not->toHaveKey('clientCode');
});

test('pending shipments are parsed with their closed state', function (): void {
    $result = cblClient([MockResponse::make(CblFixtures::response('pending-shipments'))])
        ->shipments()
        ->pending();

    expect($result)->toBeInstanceOf(PendingShipmentsData::class)
        ->and($result->shipments())->toHaveCount(1)
        ->and($result->closed())->toHaveCount(1)
        ->and($result->findByClientReference('TEST_21_08')?->carrierReference)->toBe('12481')
        ->and($result->findByClientReference('NOPE'))->toBeNull()
        ->and(lastSentBody())->not->toHaveKey('clientCode');
});

test('deleting pending shipments reports how many were removed', function (): void {
    $result = cblClient([MockResponse::make(CblFixtures::response('delete-pending-shipments'))])
        ->shipments()
        ->deletePending(['REFERENCE01']);

    expect($result->deletedShipments)->toBe(5)
        ->and($result->hasErrors())->toBeFalse();
});

test('deleting pending shipments sends the client code with the references', function (): void {
    cblClient([MockResponse::make([])])
        ->shipments()
        ->deletePending(['REFERENCE01']);

    expect(lastSentBody())->toBe([
        'clientCode' => '000000311',
        'shipmentReferences' => ['REFERENCE01'],
        'dailyToken' => '4c4ab70056299b1b948193cc87f9bcad254c473e7c7f4492cae5450ac078077c',
    ]);
});

test('deleting confirmed shipments sends the client code with the references', function (): void {
    cblClient([MockResponse::make([])])
        ->shipments()
        ->deleteConfirmed(['REFERENCE01']);

    expect(lastSentBody()['clientCode'])->toBe('000000311')
        ->and(lastSentBody()['shipmentReferences'])->toBe(['REFERENCE01']);
});

test('deleting packages sends SSCCs and no client code', function (): void {
    cblClient([MockResponse::make([])])
        ->shipments()
        ->deletePackages(['00009999999999999998']);

    expect(lastSentBody())->toBe([
        'packagesSSCC' => ['00009999999999999998'],
        'dailyToken' => '4c4ab70056299b1b948193cc87f9bcad254c473e7c7f4492cae5450ac078077c',
    ]);
});

test('reprinting sends the reference and the selected package numbers', function (): void {
    cblClient([MockResponse::make(CblFixtures::response('create-shipment-ok'))])
        ->shipments()
        ->reprint('REFERENCE01', [1, 2]);

    expect(lastSentBody()['reference'])->toBe('REFERENCE01')
        ->and(lastSentBody()['packageNumbers'])->toBe([1, 2])
        ->and(lastSentBody()['clientCode'])->toBe('000000311');
});

test('reprinting hands back labels, since CBL answers with the create envelope', function (): void {
    $result = cblClient([MockResponse::make([
        'carrierReference' => null,
        'clientReference' => null,
        'status' => 'OK',
        'packagesTags' => [
            ['packageNumber' => 1, 'sscc' => '00000000000000254717', 'tag' => '^XA^XZ'],
        ],
        'errorList' => [],
        'warningList' => [],
    ])])->shipments()->reprint('REFERENCE01', [1]);

    expect($result)->toBeInstanceOf(ShipmentResultData::class)
        ->and($result->succeeded())->toBeTrue()
        ->and($result->carrierReference)->toBeNull()
        ->and($result->labels())->toBe([1 => '^XA^XZ'])
        ->and($result->ssccs())->toBe([1 => '00000000000000254717']);
});

test('the reference list methods refuse an empty list before hitting the wire', function (string $method): void {
    $client = cblClient([]);

    expect(fn () => $client->shipments()->{$method}([]))
        ->toThrow(ValidationException::class, "{$method}() was called with an empty list");
})->with(['confirm', 'deletePending', 'deleteConfirmed', 'deletePackages']);

test('the raw request and response are retained for the caller audit log', function (): void {
    $shipments = cblClient([MockResponse::make(CblFixtures::response('create-shipment-ok'))])->shipments();

    $shipments->create(CblFixtures::shipmentData());

    expect($shipments->lastRawRequest())->toContain('"clientReference":"REFERENCE01"')
        ->and($shipments->lastRawResponse())->toContain('00000000000000254823');
});

test('a client reference longer than CBL stores is refused instead of silently truncated', function (): void {
    $client = cblClient([]);

    expect(fn () => $client->shipments()->create(CblFixtures::shipmentData([
        'clientReference' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123',
    ])))->toThrow(ValidationException::class, 'longer than the 20 characters CBL stores');
});

test('a client reference of exactly the maximum length is accepted', function (): void {
    cblClient([MockResponse::make(CblFixtures::response('create-shipment-ok'))])
        ->shipments()
        ->create(CblFixtures::shipmentData(['clientReference' => 'ABCDEFGHIJKLMNOPQRST']));

    expect(lastSentBody()['clientReference'])->toBe('ABCDEFGHIJKLMNOPQRST');
});

test('the reference list methods also refuse an over-long reference', function (string $method): void {
    $client = cblClient([]);

    expect(fn () => $client->shipments()->{$method}(['ABCDEFGHIJKLMNOPQRSTUVWXYZ0123']))
        ->toThrow(ValidationException::class, 'longer than the 20 characters CBL stores');
})->with(['confirm', 'deletePending', 'deleteConfirmed']);

test('reprint refuses an over-long reference', function (): void {
    $client = cblClient([]);

    expect(fn () => $client->shipments()->reprint('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123'))
        ->toThrow(ValidationException::class, 'longer than the 20 characters CBL stores');
});
