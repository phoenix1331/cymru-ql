<?php

declare(strict_types=1);

use App\Data\DataFiles;
use App\Models\Club;
use App\Models\ClubSeason;
use App\Models\Distance;
use App\Models\Ground;
use App\Models\League;
use App\Models\Season;
use Database\Seeders\ClubSeeder;
use Database\Seeders\GroundSeeder;
use Illuminate\Filesystem\Filesystem;

it('seeds the committed football data', function () {
    $this->seed();

    $files = app(DataFiles::class);
    $groundCount = count($files->grounds());

    expect(League::query()->count())->toBe(3)
        ->and(Season::query()->current()->sole()->slug)->toBe('2026-27')
        ->and(Ground::query()->count())->toBe($groundCount)
        ->and(Club::query()->count())->toBe(48)
        ->and(ClubSeason::query()->count())->toBe(count($files->clubSeasons()))
        ->and(Distance::query()->count())->toBe($groundCount * ($groundCount - 1));
});

it('wires relations from slugs', function () {
    $this->seed();

    $tns = Club::query()->where('slug', 'the-new-saints')->sole();
    $caernarfon = Ground::query()->where('slug', 'the-oval-caernarfon')->sole();
    $barry = Ground::query()->where('slug', 'jenner-park-barry')->sole();
    $distance = $caernarfon->distances()->where('to_ground_id', $barry->id)->sole();

    expect($tns->ground->slug)->toBe('park-hall-oswestry')
        ->and($tns->clubSeasons()->with('league', 'season')->get()->mapWithKeys(
            fn (ClubSeason $row): array => [$row->season->slug => [$row->league->slug, $row->position]],
        )->all())->toBe([
            '2025-26' => ['cymru-premier', 1],
            '2026-27' => ['cymru-premier', null],
        ])
        ->and($distance->road_km)->toBe(app(DataFiles::class)->distances()->between('the-oval-caernarfon', 'jenner-park-barry')['road_km']);
});

it('can be run twice without duplicating rows', function () {
    $this->seed();
    $this->seed();

    expect(Club::query()->count())->toBe(48)
        ->and(ClubSeason::query()->count())->toBe(count(app(DataFiles::class)->clubSeasons()));
});

it('fails loudly on a reference to an unknown slug', function () {
    $directory = sys_get_temp_dir().'/cymruql-seed-'.uniqid();
    $files = new DataFiles($directory);
    $files->write(DataFiles::GROUNDS, [['slug' => 'g', 'name' => 'G', 'town' => 'T', 'lat' => 51.5, 'lng' => -3.2]]);
    $files->write(DataFiles::CLUBS, [['slug' => 'c', 'name' => 'C', 'ground' => 'missing', 'colour' => '#ffffff']]);
    config(['cymru.data_path' => $directory]);

    try {
        $this->seed([GroundSeeder::class, ClubSeeder::class]);
    } finally {
        (new Filesystem)->deleteDirectory($directory);
    }
})->throws(RuntimeException::class, 'Seed data refers to unknown ground "missing".');
