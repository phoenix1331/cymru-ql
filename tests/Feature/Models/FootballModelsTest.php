<?php

declare(strict_types=1);

use App\Models\Club;
use App\Models\ClubSeason;
use App\Models\Ground;
use App\Models\League;
use App\Models\Season;
use Illuminate\Database\QueryException;

it('links clubs, grounds, leagues and seasons', function () {
    $ground = Ground::factory()->create();
    $club = Club::factory()->for($ground)->create();
    $league = League::factory()->create();
    $season = Season::factory()->current()->create();

    ClubSeason::factory()->for($club)->for($league)->for($season)->create(['position' => 3]);

    $clubSeason = $club->clubSeasons()->sole();

    expect($club->ground->is($ground))->toBeTrue()
        ->and($ground->clubs->sole()->is($club))->toBeTrue()
        ->and($clubSeason->league->is($league))->toBeTrue()
        ->and($clubSeason->season->is($season))->toBeTrue()
        ->and($clubSeason->club->is($club))->toBeTrue()
        ->and($clubSeason->position)->toBe(3)
        ->and($league->clubSeasons)->toHaveCount(1)
        ->and($season->clubSeasons)->toHaveCount(1)
        ->and($league->clubs->sole()->pivot->season_id)->toBe($season->id)
        ->and($club->seasons->sole()->pivot->league_id)->toBe($league->id);
});

it('routes models by slug', function (string $class) {
    expect((new $class)->getRouteKeyName())->toBe('slug');
})->with([Club::class, Ground::class, League::class, Season::class]);

it('scopes the current season', function () {
    Season::factory()->create();
    $current = Season::factory()->current()->create();

    expect(Season::query()->current()->sole()->is($current))->toBeTrue()
        ->and($current->starts_on->month)->toBe(8);
});

it('casts ground coordinates to floats', function () {
    $ground = Ground::factory()->create(['latitude' => 52.87591, 'longitude' => -3.02637])->fresh();

    expect($ground->latitude)->toBe(52.87591)
        ->and($ground->longitude)->toBe(-3.02637);
});

it('allows one league per club per season', function () {
    $clubSeason = ClubSeason::factory()->create();

    ClubSeason::factory()->create([
        'club_id' => $clubSeason->club_id,
        'season_id' => $clubSeason->season_id,
    ]);
})->throws(QueryException::class);
