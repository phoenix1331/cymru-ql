<?php

declare(strict_types=1);

use App\Models\Club;
use App\Models\ClubSeason;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Season;
use App\Services\LeagueTableService;
use App\Services\TableRow;

beforeEach(function () {
    $this->league = League::factory()->create();
    $this->season = Season::factory()->current()->create();
    $this->clubs = collect(['Aber', 'Bala', 'Caersws', 'Denbigh'])->mapWithKeys(fn (string $name): array => [
        $name => Club::factory()->create(['name' => $name]),
    ]);

    foreach ($this->clubs as $club) {
        ClubSeason::factory()->for($club)->for($this->league)->for($this->season)->create();
    }

    $this->play = fn (string $home, string $away, ?int $homeScore, ?int $awayScore, string $date = '2026-08-01') => Fixture::factory()
        ->for($this->league)->for($this->season)
        ->state($homeScore === null ? [] : ['status' => 'finished', 'home_score' => $homeScore, 'away_score' => $awayScore])
        ->create([
            'home_club_id' => $this->clubs[$home]->id,
            'away_club_id' => $this->clubs[$away]->id,
            'kicks_off_at' => $date,
        ]);
});

function summary(array $table): array
{
    return array_map(fn (TableRow $row): string => sprintf(
        '%d %s %d %d-%d-%d %d:%d %+d %d %s',
        $row->position, $row->club->name, $row->played, $row->won, $row->drawn, $row->lost,
        $row->goalsFor, $row->goalsAgainst, $row->goalDifference(), $row->points, implode('', $row->form ?? []),
    ), $table);
}

it('builds the table from finished fixtures only', function () {
    ($this->play)('Aber', 'Bala', 2, 0, '2026-08-01');
    ($this->play)('Caersws', 'Denbigh', 1, 1, '2026-08-01');
    ($this->play)('Bala', 'Caersws', 3, 1, '2026-08-08');
    ($this->play)('Denbigh', 'Aber', null, null, '2026-08-08');

    expect(summary(app(LeagueTableService::class)->table($this->league, $this->season)))->toBe([
        '1 Aber 1 1-0-0 2:0 +2 3 W',
        '2 Bala 2 1-0-1 3:3 +0 3 LW',
        '3 Denbigh 1 0-1-0 1:1 +0 1 D',
        '4 Caersws 2 0-1-1 2:4 -2 1 DL',
    ]);
});

it('breaks ties on goal difference, goals scored, then name', function () {
    ($this->play)('Aber', 'Caersws', 1, 0);
    ($this->play)('Bala', 'Denbigh', 3, 2);

    expect(array_map(fn (TableRow $row): string => $row->club->name, app(LeagueTableService::class)->table($this->league, $this->season)))
        ->toBe(['Bala', 'Aber', 'Denbigh', 'Caersws']);
});

it('lists form oldest first', function () {
    ($this->play)('Aber', 'Bala', 0, 1, '2026-08-01');
    ($this->play)('Caersws', 'Aber', 1, 1, '2026-08-08');
    ($this->play)('Aber', 'Denbigh', 2, 0, '2026-08-15');
    ($this->play)('Bala', 'Aber', 0, 3, '2026-08-22');
    ($this->play)('Aber', 'Caersws', 1, 0, '2026-08-29');
    ($this->play)('Denbigh', 'Aber', 0, 0, '2026-09-05');

    $aber = collect(app(LeagueTableService::class)->table($this->league, $this->season))->firstWhere('club.name', 'Aber');

    expect($aber->played)->toBe(6)
        ->and($aber->form)->toBe(['D', 'W', 'W', 'W', 'D']);
});

it('falls back to recorded final positions when a season has no fixtures', function () {
    $past = Season::factory()->create();
    foreach (['Denbigh' => 1, 'Bala' => 2, 'Aber' => 4] as $name => $position) {
        ClubSeason::factory()->for($this->clubs[$name])->for($this->league)->for($past)->create(['position' => $position]);
    }
    ClubSeason::factory()->for($this->clubs['Caersws'])->for($this->league)->for($past)->create();

    $table = app(LeagueTableService::class)->table($this->league, $past);

    expect(array_map(fn (TableRow $row): array => [$row->position, $row->club->name, $row->points], $table))->toBe([
        [1, 'Denbigh', null],
        [2, 'Bala', null],
        [4, 'Aber', null],
        [4, 'Caersws', null],
    ])->and($table[0]->goalDifference())->toBeNull();
});

it('shows every club on zero before a ball is kicked', function () {
    ($this->play)('Aber', 'Bala', null, null);

    expect(array_map(fn (TableRow $row): int => (int) $row->points, app(LeagueTableService::class)->table($this->league, $this->season)))
        ->toBe([0, 0, 0, 0]);
});
