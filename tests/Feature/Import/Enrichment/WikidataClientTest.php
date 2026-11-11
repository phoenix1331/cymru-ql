<?php

declare(strict_types=1);

use App\Data\ClubData;
use App\Import\Enrichment\WikidataClient;
use App\Import\Enrichment\WikidataClub;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

function binding(array $values): array
{
    return array_map(fn (string $value): array => ['value' => $value], $values);
}

it('groups wikidata entities by club and parses years and coordinates', function () {
    Http::fake([WikidataClient::ENDPOINT => Http::response(['results' => ['bindings' => [
        binding(['label' => 'Bala Town F.C.', 'club' => 'http://www.wikidata.org/entity/Q1', 'inception' => '1880-01-01T00:00:00Z',
            'coord' => 'Point(-3.601389 52.912222)', 'capacity' => '3000', 'opened' => '1951-05-01T00:00:00Z']),
        binding(['label' => 'Bala Town F.C.', 'club' => 'http://www.wikidata.org/entity/Q1', 'inception' => '1884-01-01T00:00:00Z']),
        binding(['label' => 'Rhyl F.C.', 'club' => 'http://www.wikidata.org/entity/Q2', 'dissolved' => '2020-06-01T00:00:00Z', 'inception' => 'unknown']),
        binding(['label' => 'Rhyl', 'club' => 'http://www.wikidata.org/entity/Q3', 'coord' => 'not a point']),
        binding(['label' => 'Somewhere Else F.C.', 'club' => 'http://www.wikidata.org/entity/Q4']),
    ]]])]);

    $result = (new WikidataClient('test-agent/1.0'))->findClubs([
        new ClubData('bala-town', 'Bala Town', 'maes-tegid-bala', '#fff'),
        new ClubData('rhyl', 'Rhyl', 'belle-vue-rhyl', '#fff'),
    ]);

    expect($result['bala-town'])->toEqual([
        new WikidataClub('http://www.wikidata.org/entity/Q1', 1880, false, 52.912222, -3.601389, 3000, 1951),
    ])->and($result['rhyl'])->toEqual([
        new WikidataClub('http://www.wikidata.org/entity/Q2', null, true),
        new WikidataClub('http://www.wikidata.org/entity/Q3'),
    ]);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->hasHeader('User-Agent', 'test-agent/1.0')
        && str_contains((string) $request['query'], '"C.P.D. Bala Town"@en'));
});

it('throws when wikidata errors', function () {
    Http::fake([WikidataClient::ENDPOINT => Http::response('busy', 503)]);

    (new WikidataClient('test-agent/1.0'))->findClubs([new ClubData('c', 'C', 'g', '#fff')]);
})->throws(RequestException::class);
