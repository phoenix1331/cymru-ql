<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Data\ClubData;
use App\Data\DataFiles;
use App\Data\GroundData;
use App\Import\ClubImporter;
use App\Import\ClubSeasonImporter;
use App\Import\Distances\DistanceMatrixBuilder;
use App\Import\Enrichment\Enricher;
use App\Import\OttConfigReader;
use App\Import\Overrides;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use InvalidArgumentException;
use RuntimeException;

#[Signature('cymru:import
    {--source= : Path to the Over the Terraces src/data/cymru.js (defaults to OTT_CYMRU_PATH)}
    {--enrich : Fill gaps from Wikidata and OpenStreetMap (network access, slow)}
    {--distances : Rebuild the ground distance matrix from OSRM (network access)}')]
#[Description('Regenerate database/data JSON from the Over the Terraces Cymru config')]
final class CymruImport extends Command
{
    public function handle(DataFiles $files, ClubImporter $importer, ClubSeasonImporter $clubSeasons): int
    {
        $source = (string) ($this->option('source') ?: config('cymru.ott_config_path'));

        if ($source === '') {
            $this->components->error('No source given. Pass --source or set OTT_CYMRU_PATH.');

            return self::FAILURE;
        }

        try {
            $rows = (new OttConfigReader((string) config('cymru.node_binary')))->read($source);
            $data = $importer->map($rows);
            $current = $files->currentSeason();
            $memberships = $clubSeasons->merge(
                $rows,
                $files->exists(DataFiles::CLUB_SEASONS) ? $files->clubSeasons() : [],
                $current,
                $files->leagues(),
            );
            $clubs = $this->keepExistingClubDetails($files, $data['clubs']);
            $grounds = $this->keepExistingGroundDetails($files, $data['grounds']);

            if ($this->option('enrich')) {
                $this->components->info('Enriching from Wikidata and OpenStreetMap (about one second per ground)...');

                $enriched = app(Enricher::class)->enrich($clubs, $grounds);
                [$clubs, $grounds] = [$enriched['clubs'], $enriched['grounds']];

                $this->reportEnrichment($enriched['report']);
            }

            $overrides = $files->exists(DataFiles::OVERRIDES)
                ? Overrides::fromArray($files->read(DataFiles::OVERRIDES))
                : new Overrides;
            $clubs = $overrides->applyToClubs($clubs);
            $grounds = $overrides->applyToGrounds($grounds);

            if ($this->option('distances')) {
                $distances = app(DistanceMatrixBuilder::class)->build($grounds);

                $this->components->twoColumnDetail('Ground pairs measured', (string) (count($grounds) * (count($grounds) - 1)));
                $this->components->twoColumnDetail('Pairs using the straight line fallback', (string) $distances['fallbacks']);
            }
        } catch (RuntimeException|InvalidArgumentException|RequestException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $files->writeRows(DataFiles::GROUNDS, $grounds);
        $files->writeRows(DataFiles::CLUBS, $clubs);
        $files->writeRows(DataFiles::CLUB_SEASONS, $memberships);

        if (isset($distances)) {
            $files->write(DataFiles::DISTANCES, $distances['matrix']->toArray());
        }

        $this->warnIfDistancesAreStale($files, $grounds);

        $this->components->info(sprintf(
            'Imported %d clubs, %d grounds and %s league membership into %s.',
            count($clubs),
            count($grounds),
            $current->name,
            dirname($files->path(DataFiles::CLUBS)),
        ));

        return self::SUCCESS;
    }

    /**
     * Re-importing must not lose details added by an earlier --enrich run.
     *
     * @param  list<ClubData>  $clubs
     * @return list<ClubData>
     */
    private function keepExistingClubDetails(DataFiles $files, array $clubs): array
    {
        if (! $files->exists(DataFiles::CLUBS)) {
            return $clubs;
        }

        $existing = collect($files->clubs())->keyBy('slug');

        return array_map(
            fn (ClubData $club): ClubData => $existing->has($club->slug) ? $club->fillMissingFrom($existing[$club->slug]) : $club,
            $clubs,
        );
    }

    /**
     * @param  list<GroundData>  $grounds
     * @return list<GroundData>
     */
    private function keepExistingGroundDetails(DataFiles $files, array $grounds): array
    {
        if (! $files->exists(DataFiles::GROUNDS)) {
            return $grounds;
        }

        $existing = collect($files->grounds())->keyBy('slug');

        return array_map(
            fn (GroundData $ground): GroundData => $existing->has($ground->slug) ? $ground->fillMissingFrom($existing[$ground->slug]) : $ground,
            $grounds,
        );
    }

    /**
     * @param  list<GroundData>  $grounds
     */
    private function warnIfDistancesAreStale(DataFiles $files, array $grounds): void
    {
        $expected = array_map(fn (GroundData $ground): string => $ground->slug, $grounds);

        if ($files->exists(DataFiles::DISTANCES) && $files->distances()->grounds === $expected) {
            return;
        }

        $this->components->warn('distances.json does not match the imported grounds. Run cymru:import --distances.');
    }

    /**
     * @param  array{founded: int, ground_details: int, addresses: int, unmatched: list<string>}  $report
     */
    private function reportEnrichment(array $report): void
    {
        $this->components->twoColumnDetail('Founded years added', (string) $report['founded']);
        $this->components->twoColumnDetail('Grounds given capacity or opened year', (string) $report['ground_details']);
        $this->components->twoColumnDetail('Addresses added', (string) $report['addresses']);
        $this->components->twoColumnDetail('Clubs not matched on Wikidata', (string) count($report['unmatched']));
    }
}
