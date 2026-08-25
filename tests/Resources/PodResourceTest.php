<?php

use Carbon\CarbonImmutable;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SmartDato\CblLogistica\Data\Responses\PodResultData;
use SmartDato\CblLogistica\Tests\Fixtures\CblFixtures;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

test('proof of delivery by reference decodes the base64 document', function (): void {
    $result = cblClient([MockResponse::make(CblFixtures::response('pod-by-reference'))])
        ->pod()
        ->byReference('104932869');

    expect($result)->toBeInstanceOf(PodResultData::class)
        ->and($result->documents())->toHaveCount(1)
        ->and($result->first()->carrierNumber)->toBe('104932869')
        ->and($result->first()->extension)->toBe('PDF')
        ->and($result->first()->fileExtension())->toBe('pdf')
        ->and($result->first()->decoded())->toStartWith('%PDF')
        ->and($result->first()->uploadDate)->toBeInstanceOf(CarbonImmutable::class)
        ->and(lastSentBody()['reference'])->toBe('104932869');
});

test('a shipment with no proof of delivery yields an empty list', function (): void {
    $result = cblClient([MockResponse::make(CblFixtures::response('pod-empty'))])
        ->pod()
        ->byReference('12481');

    expect($result->documents())->toBe([])
        ->and($result->first())->toBeNull();
});

test('a range wider than seven days is clamped before it is sent', function (): void {
    cblClient([MockResponse::make(CblFixtures::response('pod-empty'))])
        ->pod()
        ->byDateRange(
            CarbonImmutable::parse('2026-04-01 08:00:00'),
            CarbonImmutable::parse('2026-06-23 08:00:00'),
        );

    expect(lastSentBody()['dateFrom'])->toBe('2026-06-16T08:00:00')
        ->and(lastSentBody()['dateTo'])->toBe('2026-06-23T08:00:00');
});

test('a range within seven days is sent unchanged', function (): void {
    cblClient([MockResponse::make(CblFixtures::response('pod-empty'))])
        ->pod()
        ->byDateRange(
            CarbonImmutable::parse('2026-06-20 08:00:00'),
            CarbonImmutable::parse('2026-06-23 08:00:00'),
        );

    expect(lastSentBody()['dateFrom'])->toBe('2026-06-20T08:00:00');
});

test('a document with no binary payload decodes to null', function (): void {
    $result = cblClient([MockResponse::make([
        'podList' => [['carrierNumber' => '1', 'binaryFile' => null, 'extension' => null]],
        'errorList' => [],
        'warningList' => [],
    ])])->pod()->byReference('1');

    expect($result->first()->decoded())->toBeNull()
        ->and($result->first()->fileExtension())->toBeNull();
});
