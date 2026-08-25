<?php

use Carbon\CarbonImmutable;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SmartDato\CblLogistica\Data\Responses\StatusResultData;
use SmartDato\CblLogistica\Tests\Fixtures\CblFixtures;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

test('status by reference parses the event list', function (): void {
    $result = cblClient([MockResponse::make(CblFixtures::response('status-by-reference'))])
        ->status()
        ->byReference('999935360776');

    expect($result)->toBeInstanceOf(StatusResultData::class)
        ->and($result->events())->toHaveCount(1)
        ->and($result->events()[0]->carrierNumber)->toBe('999935360776')
        ->and($result->events()[0]->clientReference)->toBe('P01132762')
        ->and($result->events()[0]->statusCode)->toBe('ALTA')
        ->and($result->events()[0]->statusDate)->toBeInstanceOf(CarbonImmutable::class)
        ->and($result->events()[0]->statusDate->toDateTimeString())->toBe('2026-06-19 12:50:42')
        ->and($result->events()[0]->statusObservations)->toBeNull()
        ->and($result->hasErrors())->toBeFalse()
        ->and(lastSentBody()['reference'])->toBe('999935360776');
});

test('an unknown reference yields an empty event list rather than an error', function (): void {
    $result = cblClient([MockResponse::make(CblFixtures::response('status-empty'))])
        ->status()
        ->byReference('NOPE123');

    expect($result->events())->toBe([])
        ->and($result->hasErrors())->toBeFalse();
});

test('a date range within the limit is sent unchanged', function (): void {
    cblClient([MockResponse::make(CblFixtures::response('status-empty'))])
        ->status()
        ->byDateRange(
            CarbonImmutable::parse('2026-08-01 08:00:00'),
            CarbonImmutable::parse('2026-08-20 08:00:00'),
        );

    expect(lastSentBody()['dateFrom'])->toBe('2026-08-01T08:00:00')
        ->and(lastSentBody()['dateTo'])->toBe('2026-08-20T08:00:00');
});

test('a range wider than thirty days is clamped before it is sent', function (): void {
    cblClient([MockResponse::make(CblFixtures::response('status-clamped'))])
        ->status()
        ->byDateRange(
            CarbonImmutable::parse('2026-04-01 08:00:00'),
            CarbonImmutable::parse('2026-06-23 08:00:00'),
        );

    expect(lastSentBody()['dateFrom'])->toBe('2026-05-24T08:00:00')
        ->and(lastSentBody()['dateTo'])->toBe('2026-06-23T08:00:00');
});

test('the clamp warning CBL returns is still surfaced', function (): void {
    $result = cblClient([MockResponse::make(CblFixtures::response('status-clamped'))])
        ->status()
        ->byDateRange(
            CarbonImmutable::parse('2026-04-01 08:00:00'),
            CarbonImmutable::parse('2026-06-23 08:00:00'),
        );

    expect($result->hasWarnings())->toBeTrue()
        ->and($result->warningMessages())->toBe(['The date difference is bigger than maximum 30 days']);
});
