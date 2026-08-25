<?php

use SmartDato\CblLogistica\Tests\Fixtures\CblFixtures;

test('the fingerprint covers every credential field', function (string $field, string $value): void {
    $base = CblFixtures::credentials();
    $changed = CblFixtures::credentials([$field => $value]);

    expect($changed->fingerprint())->not->toBe($base->fingerprint());
})->with([
    'username' => ['username', 'SomebodyElse'],
    'password' => ['password', 'another-secret'],
    'client token' => ['clientToken', 'client-token-zzz'],
    'client code' => ['clientCode', '000000999'],
]);

test('identical accounts share a fingerprint', function (): void {
    expect(CblFixtures::credentials()->fingerprint())
        ->toBe(CblFixtures::credentials()->fingerprint());
});
