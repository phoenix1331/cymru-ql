<?php

declare(strict_types=1);

use App\Import\Enrichment\NominatimClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(fn () => Sleep::fake());

it('formats road, locality and postcode', function () {
    Http::fake([NominatimClient::ENDPOINT.'*' => Http::response(['address' => [
        'road' => "Cae'r Ymryson", 'suburb' => 'Rhosbodrual', 'town' => 'Caernarfon', 'postcode' => 'LL55 2LR', 'country' => 'United Kingdom',
    ]])]);

    expect((new NominatimClient('test-agent/1.0'))->address(53.13476, -4.26744))->toBe("Cae'r Ymryson, Caernarfon, LL55 2LR");
});

it('falls back through locality types and skips missing parts', function () {
    Http::fake([NominatimClient::ENDPOINT.'*' => Http::response(['address' => ['village' => 'Llanuwchllyn']])]);

    expect((new NominatimClient('test-agent/1.0'))->address(52.86, -3.67))->toBe('Llanuwchllyn');
});

it('returns null for failures and empty addresses', function () {
    Http::fake([NominatimClient::ENDPOINT.'*' => Http::sequence()->push('error', 500)->push(['address' => []])]);

    $client = new NominatimClient('test-agent/1.0');

    expect($client->address(51.0, -3.0))->toBeNull()
        ->and($client->address(51.0, -3.0))->toBeNull();
});

it('waits a second between requests', function () {
    Http::fake([NominatimClient::ENDPOINT.'*' => Http::response(['address' => ['town' => 'Bala']])]);

    $client = new NominatimClient('test-agent/1.0');
    $client->address(52.9, -3.6);
    $client->address(52.9, -3.6);
    $client->address(52.9, -3.6);

    Sleep::assertSleptTimes(2);
    Http::assertSent(fn ($request): bool => $request->hasHeader('User-Agent', 'test-agent/1.0') && $request['lat'] === 52.9);
});
