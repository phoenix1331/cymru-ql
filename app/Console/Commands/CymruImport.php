<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Data\DataFiles;
use App\Import\ClubImporter;
use App\Import\ClubSeasonImporter;
use App\Import\OttConfigReader;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;

#[Signature('cymru:import {--source= : Path to the Over the Terraces src/data/cymru.js (defaults to OTT_CYMRU_PATH)}')]
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
        } catch (RuntimeException|InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $files->writeRows(DataFiles::GROUNDS, $data['grounds']);
        $files->writeRows(DataFiles::CLUBS, $data['clubs']);
        $files->writeRows(DataFiles::CLUB_SEASONS, $memberships);

        $this->components->info(sprintf(
            'Imported %d clubs, %d grounds and %s league membership into %s.',
            count($data['clubs']),
            count($data['grounds']),
            $current->name,
            dirname($files->path(DataFiles::CLUBS)),
        ));

        return self::SUCCESS;
    }
}
