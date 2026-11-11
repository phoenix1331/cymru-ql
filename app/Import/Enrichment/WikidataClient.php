<?php

declare(strict_types=1);

namespace App\Import\Enrichment;

use App\Data\ClubData;
use Illuminate\Support\Facades\Http;

/**
 * Looks up football clubs on Wikidata (CC0) by English label and returns
 * founding year plus home venue facts for each candidate entity.
 */
class WikidataClient
{
    public const string ENDPOINT = 'https://query.wikidata.org/sparql';

    private const array LABEL_PATTERNS = ['%s', '%s F.C.', '%s A.F.C.', 'C.P.D. %s', '%s FC', '%s AFC'];

    public function __construct(private readonly string $userAgent) {}

    /**
     * @param  list<ClubData>  $clubs
     * @return array<string, list<WikidataClub>> candidate entities keyed by club slug
     */
    public function findClubs(array $clubs): array
    {
        $slugsByLabel = [];

        foreach ($clubs as $club) {
            foreach (self::LABEL_PATTERNS as $pattern) {
                $slugsByLabel[sprintf($pattern, $club->name)][] = $club->slug;
            }
        }

        $response = Http::withUserAgent($this->userAgent)
            ->accept('application/sparql-results+json')
            ->asForm()
            ->timeout(60)
            ->post(self::ENDPOINT, ['query' => $this->query(array_keys($slugsByLabel))])
            ->throw();

        /** @var list<array<string, array{value: string}>> $bindings */
        $bindings = $response->json('results.bindings', []);

        return $this->group($bindings, $slugsByLabel);
    }

    /**
     * @param  list<array<string, array{value: string}>>  $bindings
     * @param  array<string, list<string>>  $slugsByLabel
     * @return array<string, list<WikidataClub>>
     */
    private function group(array $bindings, array $slugsByLabel): array
    {
        /** @var array<string, array<string, WikidataClub>> $bySlug */
        $bySlug = [];

        foreach ($bindings as $binding) {
            $entity = $binding['club']['value'];

            foreach ($slugsByLabel[$binding['label']['value']] ?? [] as $slug) {
                $bySlug[$slug][$entity] ??= $this->clubFromBinding($entity, $binding);
            }
        }

        return array_map(fn (array $entities): array => array_values($entities), $bySlug);
    }

    /**
     * @param  array<string, array{value: string}>  $binding
     */
    private function clubFromBinding(string $entity, array $binding): WikidataClub
    {
        [$lng, $lat] = $this->point($binding['coord']['value'] ?? null);

        return new WikidataClub(
            entity: $entity,
            founded: $this->year($binding['inception']['value'] ?? null),
            dissolved: isset($binding['dissolved']),
            venueLat: $lat,
            venueLng: $lng,
            venueCapacity: isset($binding['capacity']) ? (int) $binding['capacity']['value'] : null,
            venueOpened: $this->year($binding['opened']['value'] ?? null),
        );
    }

    private function year(?string $date): ?int
    {
        if ($date === null || preg_match('/^(\d{4})-/', $date, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * @return array{0: ?float, 1: ?float} longitude and latitude
     */
    private function point(?string $wkt): array
    {
        if ($wkt === null || preg_match('/^Point\(([-\d.]+) ([-\d.]+)\)$/', $wkt, $matches) !== 1) {
            return [null, null];
        }

        return [(float) $matches[1], (float) $matches[2]];
    }

    /**
     * @param  list<string>  $labels
     */
    private function query(array $labels): string
    {
        $values = implode(' ', array_map(
            fn (string $label): string => json_encode($label, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).'@en',
            $labels,
        ));

        return <<<SPARQL
            SELECT ?label ?club ?inception ?dissolved ?coord ?capacity ?opened WHERE {
              VALUES ?label { {$values} }
              ?club rdfs:label ?label ; wdt:P31/wdt:P279* wd:Q476028 .
              OPTIONAL { ?club wdt:P571 ?inception }
              OPTIONAL { ?club wdt:P576 ?dissolved }
              OPTIONAL { ?club wdt:P115 ?venue .
                OPTIONAL { ?venue wdt:P625 ?coord }
                OPTIONAL { ?venue wdt:P1083 ?capacity }
                OPTIONAL { ?venue wdt:P1619 ?opened }
              }
            }
            SPARQL;
    }
}
