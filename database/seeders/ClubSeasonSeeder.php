<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Data\ClubSeasonData;
use App\Data\DataFiles;
use App\Models\Club;
use App\Models\ClubSeason;
use App\Models\League;
use App\Models\Season;
use Illuminate\Database\Seeder;

class ClubSeasonSeeder extends Seeder
{
    public function run(DataFiles $files): void
    {
        $clubs = SlugLookup::for(Club::class, 'club');
        $seasons = SlugLookup::for(Season::class, 'season');
        $leagues = SlugLookup::for(League::class, 'league');

        $rows = array_map(fn (ClubSeasonData $row): array => [
            'club_id' => $clubs->id($row->club),
            'season_id' => $seasons->id($row->season),
            'league_id' => $leagues->id($row->league),
            'position' => $row->position,
        ], $files->clubSeasons());

        ClubSeason::query()->upsert($rows, ['club_id', 'season_id'], ['league_id', 'position']);
    }
}
