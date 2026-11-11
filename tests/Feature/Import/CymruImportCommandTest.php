<?php

declare(strict_types=1);

use App\Data\ClubData;
use App\Data\ClubSeasonData;
use App\Data\DataFiles;
use App\Data\GroundData;
use App\Import\Distances\OsrmClient;
use App\Import\Enrichment\NominatimClient;
use App\Import\Enrichment\WikidataClient;
use App\Import\OttConfigReader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;

beforeEach(function () {
    $this->directory = sys_get_temp_dir().'/cymruql-import-'.uniqid();
    config(['cymru.data_path' => $this->directory, 'cymru.ott_config_path' => null]);

    (new Filesystem)->ensureDirectoryExists($this->directory);
    foreach ([DataFiles::LEAGUES, DataFiles::SEASONS] as $file) {
        copy(database_path("data/{$file}"), "{$this->directory}/{$file}");
    }
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->directory);
});

it('imports clubs and grounds from the over the terraces config', function () {
    $this->artisan('cymru:import', ['--source' => base_path('tests/Fixtures/ott-cymru.js')])
        ->expectsOutputToContain('Imported 4 clubs, 3 grounds and 2026/27 league membership')
        ->assertSuccessful();

    $files = app(DataFiles::class);
    $clubs = collect($files->clubs())->keyBy('slug');

    expect($files->grounds())->toHaveCount(3)
        ->and($clubs['trethomas-bluebirds']->ground)->toBe($clubs['caerphilly-athletic']->ground)
        ->and($clubs['caernarfon-town']->ground)->toBe('the-oval-caernarfon')
        ->and($clubs['ynyshir-albions']->ground)->toBe('the-oval-ynyshir')
        ->and($clubs['caerphilly-athletic']->nickname)->toBeNull()
        ->and(collect($files->clubSeasons())->pluck('league', 'club')->all())->toBe([
            'caernarfon-town' => 'cymru-premier',
            'ynyshir-albions' => 'cymru-south',
            'trethomas-bluebirds' => 'cymru-south',
            'caerphilly-athletic' => 'cymru-south',
        ]);
});

it('keeps hand-maintained rows for earlier seasons', function () {
    $files = app(DataFiles::class);
    $files->writeRows(DataFiles::CLUB_SEASONS, [
        new ClubSeasonData('caernarfon-town', '2025-26', 'cymru-premier', 4),
        new ClubSeasonData('caernarfon-town', '2026-27', 'cymru-north'),
    ]);

    $this->artisan('cymru:import', ['--source' => base_path('tests/Fixtures/ott-cymru.js')])->assertSuccessful();

    $caernarfon = array_values(array_filter(
        $files->clubSeasons(),
        fn (ClubSeasonData $row): bool => $row->club === 'caernarfon-town',
    ));

    expect($caernarfon)->toEqual([
        new ClubSeasonData('caernarfon-town', '2025-26', 'cymru-premier', 4),
        new ClubSeasonData('caernarfon-town', '2026-27', 'cymru-premier'),
    ]);
});

it('fails when no season is current', function () {
    app(DataFiles::class)->write(DataFiles::SEASONS, [
        ['slug' => '2025-26', 'name' => '2025/26', 'starts_on' => '2025-08-01', 'ends_on' => '2026-05-31', 'is_current' => false],
    ]);

    $this->artisan('cymru:import', ['--source' => base_path('tests/Fixtures/ott-cymru.js')])
        ->expectsOutputToContain('No season is marked as current')
        ->assertFailed();
});

it('reads the source path from config when no option is given', function () {
    config(['cymru.ott_config_path' => base_path('tests/Fixtures/ott-cymru.js')]);

    $this->artisan('cymru:import')->assertSuccessful();

    expect(app(DataFiles::class)->clubs())->toHaveCount(4);
});

it('fails without a source', function () {
    $this->artisan('cymru:import')
        ->expectsOutputToContain('No source given')
        ->assertFailed();
});

it('fails when the source file does not exist', function () {
    $this->artisan('cymru:import', ['--source' => '/nope/cymru.js'])
        ->expectsOutputToContain('config not found')
        ->assertFailed();
});

it('reports node errors', function () {
    Process::fake(['*' => Process::result(errorOutput: 'SyntaxError: Unexpected token', exitCode: 1)]);

    $this->artisan('cymru:import', ['--source' => base_path('tests/Fixtures/ott-cymru.js')])
        ->expectsOutputToContain('SyntaxError: Unexpected token')
        ->assertFailed();
});

it('rejects output that is not a list of clubs', function () {
    Process::fake(['*' => Process::result(output: '{"club": "Bala Town"}')]);

    (new OttConfigReader)->read(base_path('tests/Fixtures/ott-cymru.js'));
})->throws(RuntimeException::class, 'did not produce a list of clubs');

it('enriches clubs and grounds when asked', function () {
    Sleep::fake();
    Http::fake([
        WikidataClient::ENDPOINT => Http::response(['results' => ['bindings' => [
            binding(['label' => 'Caernarfon Town F.C.', 'club' => 'Q1', 'inception' => '1876-01-01T00:00:00Z', 'coord' => 'Point(-4.2675 53.134722)']),
        ]]]),
        NominatimClient::ENDPOINT.'*' => Http::response(['address' => ['road' => 'Marcus Street', 'town' => 'Caernarfon']]),
    ]);

    $this->artisan('cymru:import', ['--source' => base_path('tests/Fixtures/ott-cymru.js'), '--enrich' => true])
        ->expectsOutputToContain('Enriching from Wikidata')
        ->assertSuccessful();

    $files = app(DataFiles::class);

    expect(collect($files->clubs())->firstWhere('slug', 'caernarfon-town')->founded)->toBe(1876)
        ->and(collect($files->grounds())->firstWhere('slug', 'the-oval-caernarfon')->address)->toBe('Marcus Street, Caernarfon');
});

it('keeps enriched details when re-importing without enrich', function () {
    $files = app(DataFiles::class);
    $files->writeRows(DataFiles::CLUBS, [new ClubData('caernarfon-town', 'Caernarfon Town', 'the-oval-caernarfon', '#F1B434', founded: 1876)]);
    $files->writeRows(DataFiles::GROUNDS, [new GroundData('the-oval-caernarfon', 'The Oval', 'Caernarfon', 53.1, -4.2, address: 'Marcus Street, Caernarfon')]);

    $this->artisan('cymru:import', ['--source' => base_path('tests/Fixtures/ott-cymru.js')])->assertSuccessful();

    expect(collect($files->clubs())->firstWhere('slug', 'caernarfon-town')->founded)->toBe(1876)
        ->and(collect($files->grounds())->firstWhere('slug', 'the-oval-caernarfon')->address)->toBe('Marcus Street, Caernarfon')
        ->and(collect($files->grounds())->firstWhere('slug', 'the-oval-ynyshir')->address)->toBeNull();
});

it('fails cleanly when wikidata is unavailable', function () {
    Http::fake([WikidataClient::ENDPOINT => Http::response('busy', 503)]);

    $this->artisan('cymru:import', ['--source' => base_path('tests/Fixtures/ott-cymru.js'), '--enrich' => true])
        ->assertFailed();

    expect(app(DataFiles::class)->exists(DataFiles::CLUBS))->toBeFalse();
});

it('applies hand-maintained overrides last', function () {
    app(DataFiles::class)->write(DataFiles::OVERRIDES, ['clubs' => ['caernarfon-town' => ['founded' => 1876]]]);

    $this->artisan('cymru:import', ['--source' => base_path('tests/Fixtures/ott-cymru.js')])->assertSuccessful();

    expect(collect(app(DataFiles::class)->clubs())->firstWhere('slug', 'caernarfon-town')->founded)->toBe(1876);
});

it('rebuilds the distance matrix when asked', function () {
    Http::fake([OsrmClient::ENDPOINT.'*' => Http::response(['code' => 'Ok', 'distances' => [
        [0, 1000, 2000], [1000, 0, 3000], [2000, 3000, 0],
    ]])]);

    $this->artisan('cymru:import', ['--source' => base_path('tests/Fixtures/ott-cymru.js'), '--distances' => true])
        ->expectsOutputToContain('Ground pairs measured')
        ->doesntExpectOutputToContain('does not match the imported grounds')
        ->assertSuccessful();

    $matrix = app(DataFiles::class)->distances();

    expect($matrix->grounds)->toBe(['the-oval-caernarfon', 'the-oval-ynyshir', 'centre-for-sporting-excellence-ystrad-mynach'])
        ->and($matrix->between('the-oval-ynyshir', 'centre-for-sporting-excellence-ystrad-mynach')['road_km'])->toBe(3.0);
});

it('warns when the distance matrix no longer matches the grounds', function () {
    $this->artisan('cymru:import', ['--source' => base_path('tests/Fixtures/ott-cymru.js')])
        ->expectsOutputToContain('does not match the imported grounds')
        ->assertSuccessful();
});
