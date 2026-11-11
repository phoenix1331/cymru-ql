<?php

declare(strict_types=1);

use App\Enums\FixtureStatus;
use App\Models\Club;
use App\Models\ClubSeason;
use App\Models\Distance;
use App\Models\Fixture;
use App\Models\Ground;
use App\Models\League;
use App\Models\Season;
use App\Services\ClubService;
use App\Services\FixtureFilters;
use App\Services\FixtureService;
use App\Services\GroundService;
use App\Services\LeagueService;
use App\Services\SeasonService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

it('resolves seasons by slug or falls back to the current one', function () {
    $old = Season::factory()->create(['slug' => '2025-26', 'starts_on' => '2025-08-01']);
    $current = Season::factory()->current()->create(['slug' => '2026-27', 'starts_on' => '2026-08-01']);
    $service = app(SeasonService::class);

    expect($service->resolve(null)->is($current))->toBeTrue()
        ->and($service->resolve('')->is($current))->toBeTrue()
        ->and($service->resolve('2025-26')->is($old))->toBeTrue()
        ->and($service->all()->pluck('slug')->all())->toBe(['2026-27', '2025-26']);

    $service->resolve('1999-00');
})->throws(ModelNotFoundException::class);

it('lists leagues by tier and their clubs for a season', function () {
    $south = League::factory()->create(['name' => 'Cymru South', 'slug' => 'cymru-south', 'tier' => 2]);
    $premier = League::factory()->create(['name' => 'Cymru Premier', 'slug' => 'cymru-premier', 'tier' => 1]);
    $north = League::factory()->create(['name' => 'Cymru North', 'slug' => 'cymru-north', 'tier' => 2]);
    $season = Season::factory()->current()->create();
    $other = Season::factory()->create();
    ClubSeason::factory()->for($premier)->for($season)->for(Club::factory()->state(['name' => 'Zeta']))->create();
    ClubSeason::factory()->for($premier)->for($season)->for(Club::factory()->state(['name' => 'Alpha']))->create();
    ClubSeason::factory()->for($premier)->for($other)->create();
    $service = app(LeagueService::class);

    expect($service->all()->pluck('slug')->all())->toBe(['cymru-premier', 'cymru-north', 'cymru-south'])
        ->and($service->find('cymru-north')->is($north))->toBeTrue()
        ->and($service->clubs($premier, $season)->pluck('name')->all())->toBe(['Alpha', 'Zeta'])
        ->and($south->exists)->toBeTrue();
});

it('pages clubs, optionally within a league and season, and returns their history', function () {
    $league = League::factory()->create();
    $lower = League::factory()->create();
    $old = Season::factory()->create(['starts_on' => '2025-08-01']);
    $season = Season::factory()->current()->create(['starts_on' => '2026-08-01']);
    $club = Club::factory()->create(['slug' => 'bala-town', 'name' => 'Bala Town']);
    Club::factory()->count(2)->create();
    ClubSeason::factory()->for($club)->for($league)->for($old)->create(['position' => 11]);
    ClubSeason::factory()->for($club)->for($lower)->for($season)->create();
    $service = app(ClubService::class);

    expect($service->paginate(perPage: 2)->total())->toBe(3)
        ->and($service->paginate($lower, $season)->items())->toHaveCount(1)
        ->and($service->find('bala-town')->is($club))->toBeTrue()
        ->and($service->history($club)->map(fn ($row): string => "{$row->season->slug} {$row->league->slug}")->all())
        ->toBe(["{$season->slug} {$lower->slug}", "{$old->slug} {$league->slug}"])
        ->and($service->membership($club, $season)?->league->is($lower))->toBeTrue()
        ->and($service->membership(Club::factory()->create(), $season))->toBeNull();
});

it('finds the nearest grounds by road, optionally within a league', function () {
    $home = Ground::factory()->create(['name' => 'Home']);
    $near = Ground::factory()->create(['name' => 'Near']);
    $mid = Ground::factory()->create(['name' => 'Mid']);
    $far = Ground::factory()->create(['name' => 'Far']);
    foreach ([[$near, 10.5], [$mid, 30.0], [$far, 99.9]] as [$to, $km]) {
        Distance::factory()->create(['from_ground_id' => $home->id, 'to_ground_id' => $to->id, 'road_km' => $km]);
    }
    Distance::factory()->create(['from_ground_id' => $near->id, 'to_ground_id' => $home->id, 'road_km' => 11.0]);

    $league = League::factory()->create();
    $season = Season::factory()->current()->create();
    foreach ([$mid, $far] as $ground) {
        ClubSeason::factory()->for(Club::factory()->for($ground))->for($league)->for($season)->create();
    }
    $service = app(GroundService::class);
    $names = fn ($distances): array => $distances->map(fn (Distance $distance): string => $distance->toGround->name)->all();

    expect($names($service->nearby($home, 2)))->toBe(['Near', 'Mid'])
        ->and($names($service->nearby($home, 5, $league, $season)))->toBe(['Mid', 'Far'])
        ->and($names($service->distancesInLeague($home, $league, $season)))->toBe(['Mid', 'Far'])
        ->and($service->distanceBetween($near, $home)->road_km)->toBe(11.0)
        ->and($service->find($home->slug)->is($home))->toBeTrue()
        ->and($service->paginate(2)->total())->toBe(4);
});

it('filters fixtures by league, club, status, dates and round', function () {
    $season = Season::factory()->current()->create();
    $league = League::factory()->create();
    $club = Club::factory()->create();
    $base = Fixture::factory()->for($season)->for($league);

    $home = $base->for($club, 'homeClub')->finished()->create(['kicks_off_at' => '2026-08-01 13:30', 'round' => 1]);
    $away = $base->for($club, 'awayClub')->create(['kicks_off_at' => '2026-08-08 13:30', 'round' => 2]);
    $base->create(['kicks_off_at' => '2026-08-15 13:30', 'round' => 3]);
    Fixture::factory()->for($season)->create(['round' => 10]);
    Fixture::factory()->for($league)->create(['round' => 10]);
    $service = app(FixtureService::class);
    $ids = fn (FixtureFilters $filters): array => $service->query($filters)->pluck('id')->all();

    expect($ids(new FixtureFilters($season, $league)))->toHaveCount(3)
        ->and($ids(new FixtureFilters($season, club: $club)))->toBe([$home->id, $away->id])
        ->and($ids(new FixtureFilters($season, status: FixtureStatus::Finished)))->toBe([$home->id])
        ->and($ids(new FixtureFilters($season, $league, from: CarbonImmutable::parse('2026-08-05'), to: CarbonImmutable::parse('2026-08-10'))))->toBe([$away->id])
        ->and($ids(new FixtureFilters($season, round: 3)))->toHaveCount(1)
        ->and($service->paginate(new FixtureFilters($season), 2)->total())->toBe(4)
        ->and($service->find($home->id)->is($home))->toBeTrue();
});
