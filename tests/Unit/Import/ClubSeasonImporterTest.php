<?php

declare(strict_types=1);

use App\Data\ClubSeasonData;
use App\Data\LeagueData;
use App\Data\SeasonData;
use App\Import\ClubSeasonImporter;

beforeEach(function () {
    $this->current = new SeasonData('2026-27', '2026/27', '2026-08-01', '2027-05-31', true);
    $this->leagues = [
        new LeagueData('cymru-premier', 'Cymru Premier', 1, 'National'),
        new LeagueData('cymru-north', 'Cymru North', 2, 'North'),
    ];
});

it('replaces current season rows and keeps earlier seasons', function () {
    $result = (new ClubSeasonImporter)->merge(
        [['slug' => 'bala-town', 'division' => 'Cymru North']],
        [
            new ClubSeasonData('bala-town', '2025-26', 'cymru-premier', 11),
            new ClubSeasonData('bala-town', '2026-27', 'cymru-premier'),
        ],
        $this->current,
        $this->leagues,
    );

    expect($result)->toEqual([
        new ClubSeasonData('bala-town', '2025-26', 'cymru-premier', 11),
        new ClubSeasonData('bala-town', '2026-27', 'cymru-north'),
    ]);
});

it('rejects a division that is not a known league', function () {
    (new ClubSeasonImporter)->merge(
        [['slug' => 'bala-town', 'division' => 'Ardal North East']],
        [],
        $this->current,
        $this->leagues,
    );
})->throws(InvalidArgumentException::class, 'Unknown division "Ardal North East" for club bala-town.');
