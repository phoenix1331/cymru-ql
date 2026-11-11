<?php

declare(strict_types=1);

use App\Data\DataFiles;
use App\Import\OttConfigReader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->directory = sys_get_temp_dir().'/cymruql-import-'.uniqid();
    config(['cymru.data_path' => $this->directory, 'cymru.ott_config_path' => null]);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->directory);
});

it('imports clubs and grounds from the over the terraces config', function () {
    $this->artisan('cymru:import', ['--source' => base_path('tests/Fixtures/ott-cymru.js')])
        ->expectsOutputToContain('Imported 4 clubs and 3 grounds')
        ->assertSuccessful();

    $files = app(DataFiles::class);
    $clubs = collect($files->clubs())->keyBy('slug');

    expect($files->grounds())->toHaveCount(3)
        ->and($clubs['trethomas-bluebirds']->ground)->toBe($clubs['caerphilly-athletic']->ground)
        ->and($clubs['caernarfon-town']->ground)->toBe('the-oval-caernarfon')
        ->and($clubs['ynyshir-albions']->ground)->toBe('the-oval-ynyshir')
        ->and($clubs['caerphilly-athletic']->nickname)->toBeNull();
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
