<?php

/**
 * Runs the snippets the README documents, so the published usage cannot drift away
 * from the actual API.
 */

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SmartDato\CblLogistica\Cache\ArrayTokenStore;
use SmartDato\CblLogistica\CblLogistica;
use SmartDato\CblLogistica\Data\Credentials;
use SmartDato\CblLogistica\Data\Shipments\AddressData;
use SmartDato\CblLogistica\Data\Shipments\PackageData;
use SmartDato\CblLogistica\Data\Shipments\ShipmentData;
use SmartDato\CblLogistica\Exceptions\ValidationException;
use SmartDato\CblLogistica\Tests\Fixtures\CblFixtures;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

test('the documented create snippet works exactly as written', function (): void {
    MockClient::global([
        MockResponse::make(['dailyToken' => 'test-token']),
        MockResponse::make(CblFixtures::response('create-shipment-ok')),
    ]);

    $cbl = new CblLogistica(tokenStore: new ArrayTokenStore)
        ->withCredentials(new Credentials(
            username: 'YourUser',
            password: 'YourPassword',
            clientToken: 'a1b2c3',
            clientCode: '000000311',
        ));

    $result = $cbl->shipments()->create(new ShipmentData(
        clientReference: 'ORDER-4242',
        sender: new AddressData(
            name: 'OMEST SRL',
            street: 'Via L. Negrelli 15',
            postalCode: '39100',
            city: 'BOLZANO',
            country: 'IT',
            province: 'BZ',
        ),
        receiver: new AddressData(
            name: 'Josép Peñá',
            street: 'Calle Mayor 1',
            postalCode: '08029',
            city: 'BARCELONA',
            country: 'ES',
            province: 'BARCELONA',
            phone: '111222444',
            nif: '123456789B',
            email: 'jose@example.com',
        ),
        numPackages: 2,
        weight: 2.0,
        volume: 0.02,
        packages: [
            new PackageData(packageNumber: 1, width: 0.2, height: 0.2, depth: 0.2, weight: 1.0),
            new PackageData(packageNumber: 2, width: 0.2, height: 0.2, depth: 0.2, weight: 1.0),
        ],
        observations1: 'Call before delivery',
        carrier: 'SALVAT',
    ));

    expect($result->succeeded())->toBeTrue()
        ->and($result->carrierReference)->toBe('12527')
        ->and($result->labels())->toHaveCount(2)
        ->and($result->ssccs())->toHaveCount(2)
        ->and($result->errorMessages())->toBe([])
        ->and($result->warningMessages())->toBe([]);
});

test('the documented faking snippet queues the token first', function (): void {
    MockClient::global([
        MockResponse::make(['dailyToken' => 'test-token']),
        MockResponse::make(['carrierReference' => '12527', 'status' => 'OK', 'packagesTags' => []]),
    ]);

    $result = new CblLogistica(tokenStore: new ArrayTokenStore)
        ->withCredentials(CblFixtures::credentials())
        ->shipments()
        ->create(CblFixtures::shipmentData());

    expect(MockClient::getGlobal()->getLastPendingRequest()->body()->all())
        ->toHaveKey('dailyToken')
        ->and($result->succeeded())->toBeTrue()
        ->and($result->labels())->toBe([]);
});

test('the documented multi-account snippet keeps the base instance credential-free', function (): void {
    $base = new CblLogistica(tokenStore: new ArrayTokenStore);

    $omest = $base->withCredentials(CblFixtures::credentials());
    $other = $base->withCredentials(CblFixtures::otherCredentials());

    expect($base->credentials())->toBeNull()
        ->and($omest->credentials()?->clientCode)->toBe('000000311')
        ->and($other->credentials()?->clientCode)->toBe('000000999');
});

test('using the package without credentials names the call that is missing', function (): void {
    $cbl = new CblLogistica(tokenStore: new ArrayTokenStore);

    expect(fn () => $cbl->shipments())
        ->toThrow(ValidationException::class, 'call withCredentials() first');
});
