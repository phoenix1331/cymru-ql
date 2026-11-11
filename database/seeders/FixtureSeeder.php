<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Season;
use App\Simulation\SeasonGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class FixtureSeeder extends Seeder
{
    public function run(SeasonGenerator $generator): void
    {
        $asOf = config('cymru.simulation.as_of');

        $generator->generate(
            Season::query()->current()->sole(),
            $asOf ? CarbonImmutable::parse((string) $asOf) : CarbonImmutable::now(),
        );
    }
}
