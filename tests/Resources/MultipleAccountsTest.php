<?php

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SmartDato\CblLogistica\Auth\CblAuthenticator;
use SmartDato\CblLogistica\Cache\ArrayTokenStore;
use SmartDato\CblLogistica\CblLogistica;
use SmartDato\CblLogistica\Tests\Fixtures\CblFixtures;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

test('two accounts of the same carrier authenticate as themselves', function (): void {
    MockClient::global([
        MockResponse::make(['dailyToken' => 'token-for-first']),
        MockResponse::make(CblFixtures::response('pending-shipments')),
        MockResponse::make(['dailyToken' => 'token-for-second']),
        MockResponse::make(CblFixtures::response('pending-shipments')),
    ]);

    $base = new CblLogistica(tokenStore: new ArrayTokenStore);

    $first = $base->withCredentials(CblFixtures::credentials());
    $second = $base->withCredentials(CblFixtures::otherCredentials());

    $first->shipments()->pending();
    $firstAuth = MockClient::getGlobal()->getLastPendingRequest()->headers()->get('Authorization');
    $firstBody = lastSentBody();

    $second->shipments()->pending();
    $secondAuth = MockClient::getGlobal()->getLastPendingRequest()->headers()->get('Authorization');
    $secondBody = lastSentBody();

    expect($firstAuth)->toBe('Basic '.base64_encode('OmestTest:secret'))
        ->and($secondAuth)->toBe('Basic '.base64_encode('OtherAccount:other-secret'))
        ->and($firstBody['dailyToken'])->toBe('token-for-first')
        ->and($secondBody['dailyToken'])->toBe('token-for-second');
});

test('a clone never inherits the resource of the account it was cloned from', function (): void {
    $base = new CblLogistica(tokenStore: new ArrayTokenStore);

    $first = $base->withCredentials(CblFixtures::credentials());
    $second = $first->withCredentials(CblFixtures::otherCredentials());

    expect($second->shipments())->not->toBe($first->shipments())
        ->and($second->status())->not->toBe($first->status())
        ->and($second->pod())->not->toBe($first->pod());
});

test('each account gets its own token cache key', function (): void {
    $store = new ArrayTokenStore;

    $first = new CblAuthenticator(CblFixtures::credentials(), $store);
    $second = new CblAuthenticator(CblFixtures::otherCredentials(), $store);

    expect($first->cacheKey())->not->toBe($second->cacheKey())
        ->and($first->cacheKey())->toStartWith('cbl-logistica:daily-token:');
});

test('one account never receives a token cached for another', function (): void {
    MockClient::global([
        MockResponse::make(['dailyToken' => 'token-for-first']),
        MockResponse::make(['dailyToken' => 'token-for-second']),
    ]);

    $store = new ArrayTokenStore;

    $first = new CblAuthenticator(CblFixtures::credentials(), $store);
    $second = new CblAuthenticator(CblFixtures::otherCredentials(), $store);

    expect($first->dailyToken())->toBe('token-for-first')
        ->and($second->dailyToken())->toBe('token-for-second')
        ->and($first->dailyToken())->toBe('token-for-first');
});

test('the account client code is taken from the credentials, never from the caller', function (): void {
    cblClient(
        [MockResponse::make(CblFixtures::response('create-shipment-ok'))],
        CblFixtures::otherCredentials(),
    )->shipments()->create(CblFixtures::shipmentData());

    expect(lastSentBody()['clientCode'])->toBe('000000999');
});
