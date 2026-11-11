<?php

declare(strict_types=1);

namespace App\Import\Enrichment;

use App\Data\ClubData;
use App\Data\GroundData;
use App\Support\Geo;

/**
 * Fills gaps in the imported clubs and grounds from Wikidata (founded year,
 * ground capacity and opened year) and OpenStreetMap (ground address).
 * Existing values are never overwritten.
 */
final readonly class Enricher
{
    /** A Wikidata venue further than this from our ground is treated as a different ground. */
    public const float VENUE_MATCH_KM = 2.0;

    public function __construct(
        private WikidataClient $wikidata,
        private NominatimClient $nominatim,
    ) {}

    /**
     * @param  list<ClubData>  $clubs
     * @param  list<GroundData>  $grounds
     * @return array{clubs: list<ClubData>, grounds: list<GroundData>, report: array{founded: int, ground_details: int, addresses: int, unmatched: list<string>}}
     */
    public function enrich(array $clubs, array $grounds): array
    {
        $candidates = $this->wikidata->findClubs($clubs);

        /** @var array<string, GroundData> $groundsBySlug */
        $groundsBySlug = [];
        foreach ($grounds as $ground) {
            $groundsBySlug[$ground->slug] = $ground;
        }

        $report = ['founded' => 0, 'ground_details' => 0, 'addresses' => 0, 'unmatched' => []];

        foreach ($clubs as $index => $club) {
            $ground = $groundsBySlug[$club->ground];
            $match = $this->pick($candidates[$club->slug] ?? [], $ground);

            if ($match === null) {
                $report['unmatched'][] = $club->slug;

                continue;
            }

            if ($club->founded === null && $match->founded !== null) {
                $clubs[$index] = $club->withMissingDetails(founded: $match->founded);
                $report['founded']++;
            }

            if (! $this->venueIsGround($match, $ground)) {
                continue;
            }

            $enriched = $ground->withMissingDetails(capacity: $match->venueCapacity, opened: $match->venueOpened);
            $report['ground_details'] += (int) ($enriched != $ground);
            $groundsBySlug[$ground->slug] = $enriched;
        }

        foreach ($groundsBySlug as $slug => $ground) {
            if ($ground->address !== null) {
                continue;
            }

            $address = $this->nominatim->address($ground->lat, $ground->lng);
            $groundsBySlug[$slug] = $ground->withMissingDetails(address: $address);
            $report['addresses'] += (int) ($address !== null);
        }

        return ['clubs' => $clubs, 'grounds' => array_values($groundsBySlug), 'report' => $report];
    }

    /**
     * Chooses the single Wikidata entity that represents the club, or null
     * when there is none or the choice is ambiguous.
     *
     * @param  list<WikidataClub>  $candidates
     */
    private function pick(array $candidates, GroundData $ground): ?WikidataClub
    {
        $active = array_values(array_filter($candidates, fn (WikidataClub $club): bool => ! $club->dissolved));

        if (count($active) === 1) {
            return $active[0];
        }

        $nearby = array_values(array_filter($active, fn (WikidataClub $club): bool => $this->venueIsGround($club, $ground)));

        return count($nearby) === 1 ? $nearby[0] : null;
    }

    private function venueIsGround(WikidataClub $club, GroundData $ground): bool
    {
        if (! $club->hasVenueCoordinates()) {
            return false;
        }

        return Geo::haversineKm($ground->lat, $ground->lng, (float) $club->venueLat, (float) $club->venueLng) <= self::VENUE_MATCH_KM;
    }
}
