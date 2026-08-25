<?php

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SmartDato\CblLogistica\Cache\ArrayTokenStore;
use SmartDato\CblLogistica\CblLogistica;
use SmartDato\CblLogistica\Data\Credentials;
use SmartDato\CblLogistica\Tests\Fixtures\CblFixtures;
use SmartDato\CblLogistica\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * A client whose token endpoint and API both answer from the mock queue. The
 * daily-token call is always first, because the authenticator fetches it before
 * the first real request goes out.
 *
 * @param  array<int, MockResponse>  $responses
 */
function cblClient(array $responses, ?Credentials $credentials = null): CblLogistica
{
    MockClient::global([
        MockResponse::make(CblFixtures::response('daily-token')),
        ...$responses,
    ]);

    return new CblLogistica(tokenStore: new ArrayTokenStore)
        ->withCredentials($credentials ?? CblFixtures::credentials());
}

/**
 * The body of the request that was sent last.
 *
 * @return array<string, mixed>
 */
function lastSentBody(): array
{
    $body = MockClient::getGlobal()->getLastPendingRequest()?->body()?->all();

    return is_array($body) ? $body : [];
}
