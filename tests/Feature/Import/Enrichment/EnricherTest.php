<?php

declare(strict_types=1);

use App\Data\ClubData;
use App\Data\GroundData;
use App\Import\Enrichment\Enricher;
use App\Import\Enrichment\NominatimClient;
use App\Import\Enrichment\WikidataClient;
use App\Import\Enrichment\WikidataClub;

function enricher(array $candidates, ?string $address = 'Station Road, Bala, LL23 7YG'): Enricher
{
    $wikidata = Mockery::mock(WikidataClient::class);
    $wikidata->shouldReceive('findClubs')->andReturn($candidates);

    $nominatim = Mockery::mock(NominatimClient::class);
    $nominatim->shouldReceive('address')->andReturn($address);

    return new Enricher($wikidata, $nominatim);
}

beforeEach(function () {
    $this->ground = new GroundData('maes-tegid-bala', 'Maes Tegid', 'Bala', 52.9089, -3.5963, capacity: 3000);
    $this->club = new ClubData('bala-town', 'Bala Town', 'maes-tegid-bala', '#fff');
});

it('fills founded year, ground details and address', function () {
    $result = enricher(['bala-town' => [new WikidataClub('Q1', 1880, false, 52.9122, -3.6014, 2500, 1951)]])
        ->enrich([$this->club], [$this->ground]);

    expect($result['clubs'][0]->founded)->toBe(1880)
        ->and($result['grounds'][0]->capacity)->toBe(3000)
        ->and($result['grounds'][0]->opened)->toBe(1951)
        ->and($result['grounds'][0]->address)->toBe('Station Road, Bala, LL23 7YG')
        ->and($result['report'])->toBe(['founded' => 1, 'ground_details' => 1, 'addresses' => 1, 'unmatched' => []]);
});

it('ignores dissolved clubs and venue data from far away grounds', function () {
    $result = enricher(['bala-town' => [
        new WikidataClub('Q-old', 1870, true, 52.9122, -3.6014, 9000, 1900),
        new WikidataClub('Q1', 1880, false, 51.47, -3.21, 4953, 2009),
    ]])->enrich([$this->club], [$this->ground]);

    expect($result['clubs'][0]->founded)->toBe(1880)
        ->and($result['grounds'][0]->opened)->toBeNull()
        ->and($result['report']['ground_details'])->toBe(0);
});

it('resolves ambiguous names by venue proximity', function () {
    $result = enricher(['bala-town' => [
        new WikidataClub('Q-other', 1977),
        new WikidataClub('Q1', 1880, false, 52.9122, -3.6014),
    ]])->enrich([$this->club], [$this->ground]);

    expect($result['clubs'][0]->founded)->toBe(1880);
});

it('leaves clubs alone when the match is ambiguous or missing', function () {
    $ambiguous = enricher(['bala-town' => [new WikidataClub('Q1', 1880), new WikidataClub('Q2', 1977)]], null)
        ->enrich([$this->club], [$this->ground]);
    $missing = enricher([], null)->enrich([$this->club], [$this->ground]);

    expect($ambiguous['clubs'][0]->founded)->toBeNull()
        ->and($ambiguous['report']['unmatched'])->toBe(['bala-town'])
        ->and($ambiguous['report']['addresses'])->toBe(0)
        ->and($missing['report']['unmatched'])->toBe(['bala-town']);
});

it('never overwrites existing values or looks up known addresses', function () {
    $wikidata = Mockery::mock(WikidataClient::class);
    $wikidata->shouldReceive('findClubs')->andReturn(['bala-town' => [new WikidataClub('Q1', 1880, false, 52.9122, -3.6014, 2500, 1951)]]);
    $nominatim = Mockery::mock(NominatimClient::class);
    $nominatim->shouldNotReceive('address');

    $result = (new Enricher($wikidata, $nominatim))->enrich(
        [$this->club->withMissingDetails(founded: 1884)],
        [$this->ground->withMissingDetails(opened: 1950, address: 'Known')],
    );

    expect($result['clubs'][0]->founded)->toBe(1884)
        ->and($result['grounds'][0]->opened)->toBe(1950)
        ->and($result['report'])->toBe(['founded' => 0, 'ground_details' => 0, 'addresses' => 0, 'unmatched' => []]);
});
