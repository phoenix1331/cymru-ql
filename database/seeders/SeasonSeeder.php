<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Data\DataFiles;
use App\Data\SeasonData;
use App\Models\Season;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    public function run(DataFiles $files): void
    {
        Season::query()->upsert(
            array_map(fn (SeasonData $season): array => $season->toArray(), $files->seasons()),
            ['slug'],
            ['name', 'starts_on', 'ends_on', 'is_current'],
        );
    }
}
