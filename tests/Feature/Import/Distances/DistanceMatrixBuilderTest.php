<?php

declare(strict_types=1);

use App\Data\GroundData;
use App\Import\Distances\DistanceMatrixBuilder;
use App\Import\Distances\OsrmClient;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->grounds = [
        new GroundData('the-oval-caernarfon', 'The Oval', 'Caernarfon', 53.13476, -4.26744),
        new GroundData('jenner-park-barry', 'Jenner Park', 'Barry', 51.41087, -3.2655),
    ];
});

it('builds road and straight line matrices from osrm', function () {
    Carbon::setTestNow('2026-11-11 10:00');
    Http::fake([OsrmClient::ENDPOINT.'*' => Http::response(['code' => 'Ok', 'distances' => [[0, 275_449.6], [276_012.2, 0]]])]);

    $result = (new DistanceMatrixBuilder(new OsrmClient('test-agent/1.0')))->build($this->grounds);
    $matrix = $result['matrix'];

    expect($matrix->generatedAt)->toBe('2026-11-11')
        ->and($matrix->grounds)->toBe(['the-oval-caernarfon', 'jenner-park-barry'])
        ->and($matrix->roadKm)->toBe([[0.0, 275.4], [276.0, 0.0]])
        ->and($matrix->straightKm[0][1])->toBe($matrix->straightKm[1][0])
        ->and($matrix->straightKm[0][1])->toBeBetween(200.0, 210.0)
        ->and($result['fallbacks'])->toBe(0);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '-4.26744,53.13476;-3.2655,51.41087')
        && $request['annotations'] === 'distance'
        && $request->hasHeader('User-Agent', 'test-agent/1.0'));
});

it('falls back to straight line times the road factor for unroutable pairs', function () {
    Http::fake([OsrmClient::ENDPOINT.'*' => Http::response(['code' => 'Ok', 'distances' => [[0, null], [276_012.2, 0]]])]);

    $result = (new DistanceMatrixBuilder(new OsrmClient('test-agent/1.0')))->build($this->grounds);

    expect($result['fallbacks'])->toBe(1)
        ->and($result['matrix']->roadKm[0][1])->toEqualWithDelta($result['matrix']->straightKm[0][1] * 1.3, 0.1);
});

it('fails when osrm reports an error', function () {
    Http::fake([OsrmClient::ENDPOINT.'*' => Http::response(['code' => 'TooBig', 'message' => 'Too many table coordinates'])]);

    (new OsrmClient('test-agent/1.0'))->distances($this->grounds);
})->throws(RuntimeException::class, 'Too many table coordinates');

it('fails when osrm is unavailable', function () {
    Http::fake([OsrmClient::ENDPOINT.'*' => Http::response('', 502)]);

    (new OsrmClient('test-agent/1.0'))->distances($this->grounds);
})->throws(RequestException::class);
