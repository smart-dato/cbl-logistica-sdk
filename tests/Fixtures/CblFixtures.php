<?php

namespace SmartDato\CblLogistica\Tests\Fixtures;

use SmartDato\CblLogistica\Data\Credentials;
use SmartDato\CblLogistica\Data\Shipments\AddressData;
use SmartDato\CblLogistica\Data\Shipments\PackageData;
use SmartDato\CblLogistica\Data\Shipments\ShipmentData;

/**
 * DTO builders shared by the offline tests, alongside the recorded wire payloads
 * in tests/Fixtures/responses.
 */
class CblFixtures
{
    public static function credentials(array $overrides = []): Credentials
    {
        return new Credentials(...array_merge([
            'username' => 'OmestTest',
            'password' => 'secret',
            'clientToken' => 'client-token-aaa',
            'clientCode' => '000000311',
        ], $overrides));
    }

    public static function otherCredentials(): Credentials
    {
        return self::credentials([
            'username' => 'OtherAccount',
            'password' => 'other-secret',
            'clientToken' => 'client-token-bbb',
            'clientCode' => '000000999',
        ]);
    }

    public static function sender(array $overrides = []): AddressData
    {
        return new AddressData(...array_merge([
            'name' => 'OMEST SRL',
            'street' => 'Via L. Negrelli 15',
            'postalCode' => '39100',
            'city' => 'BOLZANO',
            'country' => 'IT',
            'province' => 'BZ',
            'phone' => '390471196231',
            'contactPerson' => 'Logistica',
            'nif' => '01234567890',
        ], $overrides));
    }

    public static function receiver(array $overrides = []): AddressData
    {
        return new AddressData(...array_merge([
            'name' => 'Receiver SL',
            'street' => 'Calle Mayor 1',
            'postalCode' => '08029',
            'city' => 'BARCELONA',
            'country' => 'ES',
            'province' => 'BARCELONA',
            'phone' => '111222444',
            'contactPerson' => 'Martin',
            'nif' => '123456789B',
            'email' => 'martin@example.com',
        ], $overrides));
    }

    public static function shipmentData(array $overrides = []): ShipmentData
    {
        return new ShipmentData(...array_merge([
            'clientReference' => 'REFERENCE01',
            'sender' => self::sender(),
            'receiver' => self::receiver(),
            'numPackages' => 2,
            'weight' => 2.0,
            'volume' => 0.2,
            'packages' => [
                new PackageData(packageNumber: 1, width: 1.0, height: 2.0, depth: 3.0, weight: 1.0),
                new PackageData(packageNumber: 2, width: 1.0, height: 2.0, depth: 3.0, weight: 1.0),
            ],
            'observations1' => 'Call before delivery',
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    public static function response(string $name): array
    {
        return json_decode((string) file_get_contents(__DIR__."/responses/{$name}.json"), true);
    }
}
