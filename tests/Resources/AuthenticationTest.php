<?php

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SmartDato\CblLogistica\Auth\CblAuthenticator;
use SmartDato\CblLogistica\Auth\Requests\GetDailyTokenRequest;
use SmartDato\CblLogistica\Cache\ArrayTokenStore;
use SmartDato\CblLogistica\Exceptions\CblLogisticaApiException;
use SmartDato\CblLogistica\Tests\Fixtures\CblFixtures;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

test('every request carries basic auth in the headers and the daily token in the body', function (): void {
    cblClient([MockResponse::make(CblFixtures::response('pending-shipments'))])
        ->shipments()
        ->pending();

    $pendingRequest = MockClient::getGlobal()->getLastPendingRequest();

    expect($pendingRequest->headers()->get('Authorization'))
        ->toBe('Basic '.base64_encode('OmestTest:secret'))
        ->and(lastSentBody())
        ->toBe(['dailyToken' => '4c4ab70056299b1b948193cc87f9bcad254c473e7c7f4492cae5450ac078077c']);
});

test('the daily token is fetched once and then served from the store', function (): void {
    $client = cblClient([
        MockResponse::make(CblFixtures::response('pending-shipments')),
        MockResponse::make(CblFixtures::response('pending-shipments')),
    ]);

    $client->shipments()->pending();
    $client->shipments()->pending();

    // Three calls in total: one token fetch plus the two pending lookups.
    MockClient::getGlobal()->assertSentCount(3);
    MockClient::getGlobal()->assertSentCount(1, GetDailyTokenRequest::class);
});

test('the token is cached until the end of the day, not for a fixed window', function (): void {
    MockClient::global([MockResponse::make(CblFixtures::response('daily-token'))]);

    $store = new ArrayTokenStore;
    $authenticator = new CblAuthenticator(CblFixtures::credentials(), $store);

    $authenticator->dailyToken();

    expect($store->get($authenticator->cacheKey()))
        ->toBe('4c4ab70056299b1b948193cc87f9bcad254c473e7c7f4492cae5450ac078077c');
});

test('a token response without a dailyToken is reported rather than sent as empty', function (): void {
    MockClient::global([MockResponse::make(['dailyToken' => ''])]);

    $authenticator = new CblAuthenticator(CblFixtures::credentials(), new ArrayTokenStore);

    expect(fn () => $authenticator->dailyToken())
        ->toThrow(CblLogisticaApiException::class, 'CBL returned no dailyToken for client code 000000311.');
});

test('an empty 401 body produces a readable exception instead of a JSON decode error', function (): void {
    MockClient::global([MockResponse::make(body: '', status: 401)]);

    $authenticator = new CblAuthenticator(CblFixtures::credentials(), new ArrayTokenStore);

    expect(fn () => $authenticator->dailyToken())
        ->toThrow(CblLogisticaApiException::class, 'CBL rejected the credentials (HTTP 401).');
});

test('an api failure after authentication also surfaces as a package exception', function (): void {
    $client = cblClient([MockResponse::make(body: '', status: 401)]);

    expect(fn () => $client->shipments()->pending())
        ->toThrow(CblLogisticaApiException::class);
});
