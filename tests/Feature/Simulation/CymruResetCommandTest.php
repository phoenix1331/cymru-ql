<?php

declare(strict_types=1);

use App\Enums\FixtureStatus;
use App\Models\Fixture;
use Illuminate\Support\Facades\Artisan;

it('reseeds everything and simulates results up to the given date', function () {
    // migrate:fresh cannot run inside the test transaction on in-memory SQLite,
    // so stand in a command that only reseeds.
    Artisan::command('migrate:fresh {--seed} {--force}', function () {
        $this->call('db:seed', ['--force' => true]);
    });

    $this->artisan('cymru:reset', ['--as-of' => '2026-08-02'])
        ->expectsOutputToContain('Reset complete: 720 fixtures, 24 played.')
        ->assertSuccessful();

    expect(Fixture::query()->where('status', FixtureStatus::Finished)->max('round'))->toBe(1);
});

it('rejects an as-of value it cannot parse', function () {
    $this->artisan('cymru:reset', ['--as-of' => 'not a date'])
        ->expectsOutputToContain('Could not understand --as-of')
        ->assertFailed();
});

it('does nothing in production without confirmation', function () {
    $this->app['env'] = 'production';

    $this->artisan('cymru:reset')
        ->expectsOutputToContain('Command cancelled')
        ->assertFailed();

    expect(Fixture::query()->count())->toBe(0);
});
