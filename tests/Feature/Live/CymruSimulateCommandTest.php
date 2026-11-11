<?php

declare(strict_types=1);

use App\Enums\FixtureStatus;
use App\Models\Club;
use App\Models\ClubSeason;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Season;
use App\Simulation\SeasonGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Sleep;

beforeEach(function () {
    Sleep::fake();

    $this->season = Season::factory()->current()->create(['name' => '2026/27', 'starts_on' => '2026-08-01', 'ends_on' => '2027-05-31']);
    $league = League::factory()->create();
    foreach (Club::factory()->count(4)->create() as $club) {
        ClubSeason::factory()->for($club)->for($league)->for($this->season)->create();
    }

    app(SeasonGenerator::class)->generate($this->season, CarbonImmutable::parse('2026-07-01'));
});

it('plays the requested number of rounds live', function () {
    $this->artisan('cymru:simulate', ['--rounds' => 2, '--tick' => 0, '--pause' => 5])
        ->expectsOutputToContain('Kick off: round 1, 2 fixtures.')
        ->expectsOutputToContain('Kick off: round 2, 2 fixtures.')
        ->expectsOutputToContain('Full time,')
        ->assertSuccessful();

    expect(Fixture::query()->where('status', FixtureStatus::Finished)->pluck('round')->unique()->sort()->values()->all())->toBe([1, 2]);
    Sleep::assertSlept(fn ($duration): bool => $duration->totalSeconds === 5.0, 1);
});

it('stops when the season is over', function () {
    Fixture::query()->update(['status' => FixtureStatus::Finished]);

    $this->artisan('cymru:simulate', ['--tick' => 0])
        ->expectsOutputToContain('Every 2026/27 fixture has been played.')
        ->assertSuccessful();
});

it('keeps going until the season ends when no round limit is set', function () {
    $this->artisan('cymru:simulate', ['--tick' => 0, '--pause' => 0])
        ->expectsOutputToContain('Kick off: round 6, 2 fixtures.')
        ->expectsOutputToContain('Every 2026/27 fixture has been played.')
        ->assertSuccessful();
});
