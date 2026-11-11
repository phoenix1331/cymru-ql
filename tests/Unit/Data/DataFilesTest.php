<?php

declare(strict_types=1);

use App\Data\ClubSeasonData;
use App\Data\DataFiles;
use App\Data\LeagueData;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->directory = sys_get_temp_dir().'/cymruql-data-'.uniqid();
    $this->files = new DataFiles($this->directory);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->directory);
});

it('writes pretty json and reads it back as data objects', function () {
    $this->files->writeRows(DataFiles::CLUB_SEASONS, [
        new ClubSeasonData('caernarfon-town', '2026-27', 'cymru-premier'),
    ]);

    expect(file_get_contents($this->files->path(DataFiles::CLUB_SEASONS)))
        ->toContain("\n    {\n")
        ->toEndWith("]\n")
        ->and($this->files->clubSeasons())->toEqual([
            new ClubSeasonData('caernarfon-town', '2026-27', 'cymru-premier'),
        ]);
});

it('keeps unicode and slashes readable', function () {
    $this->files->write('sample.json', ['name' => "Connah's Quay – Nomads", 'url' => 'https://example.test/a']);

    expect(file_get_contents($this->files->path('sample.json')))
        ->toContain('Connah\'s Quay – Nomads')
        ->toContain('https://example.test/a');
});

it('fails clearly when a file is missing', function () {
    $this->files->leagues();
})->throws(RuntimeException::class, 'Data file not found');

it('fails when a file holds a scalar', function () {
    (new Filesystem)->ensureDirectoryExists($this->directory);
    file_put_contents($this->files->path(DataFiles::LEAGUES), '"nope"');

    $this->files->leagues();
})->throws(RuntimeException::class, 'does not contain a JSON array or object');

it('fails when a row is not an object', function () {
    $this->files->write(DataFiles::LEAGUES, ['not-a-row']);

    $this->files->leagues();
})->throws(RuntimeException::class, 'non-object row');

it('reads every typed collection', function () {
    $this->files->writeRows(DataFiles::LEAGUES, [new LeagueData('cymru-south', 'Cymru South', 2, 'South')]);
    $this->files->write(DataFiles::SEASONS, [['slug' => '2026-27', 'name' => '2026/27', 'starts_on' => '2026-08-01', 'ends_on' => '2027-05-31', 'is_current' => true]]);
    $this->files->write(DataFiles::GROUNDS, [['slug' => 'g', 'name' => 'G', 'town' => 'T', 'lat' => 51.5, 'lng' => -3.2]]);
    $this->files->write(DataFiles::CLUBS, [['slug' => 'c', 'name' => 'C', 'ground' => 'g', 'colour' => '#fff']]);

    expect($this->files->leagues())->toHaveCount(1)
        ->and($this->files->seasons()[0]->isCurrent)->toBeTrue()
        ->and($this->files->grounds()[0]->town)->toBe('T')
        ->and($this->files->clubs()[0]->ground)->toBe('g');
});
