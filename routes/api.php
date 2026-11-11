<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ClubController;
use App\Http\Controllers\Api\DistanceController;
use App\Http\Controllers\Api\FixtureController;
use App\Http\Controllers\Api\GroundController;
use App\Http\Controllers\Api\LeagueController;
use App\Http\Controllers\Api\SeasonController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::get('seasons', SeasonController::class)->name('seasons.index');

    Route::get('leagues', [LeagueController::class, 'index'])->name('leagues.index');
    Route::get('leagues/{league}', [LeagueController::class, 'show'])->name('leagues.show');
    Route::get('leagues/{league}/table', [LeagueController::class, 'table'])->name('leagues.table');

    Route::get('clubs', [ClubController::class, 'index'])->name('clubs.index');
    Route::get('clubs/{club}', [ClubController::class, 'show'])->name('clubs.show');
    Route::get('clubs/{club}/seasons', [ClubController::class, 'seasons'])->name('clubs.seasons');

    Route::get('grounds', [GroundController::class, 'index'])->name('grounds.index');
    Route::get('grounds/{ground}', [GroundController::class, 'show'])->name('grounds.show');
    Route::get('grounds/{ground}/nearby', [DistanceController::class, 'nearby'])->name('grounds.nearby');
    Route::get('grounds/{ground}/distances', [DistanceController::class, 'inLeague'])->name('grounds.distances');
    Route::get('grounds/{from}/distances/{to}', [DistanceController::class, 'between'])->name('grounds.distances.show');

    Route::get('fixtures', [FixtureController::class, 'index'])->name('fixtures.index');
    Route::get('fixtures/{fixture}', [FixtureController::class, 'show'])->whereNumber('fixture')->name('fixtures.show');
    Route::get('fixtures/{fixture}/events', [FixtureController::class, 'events'])->whereNumber('fixture')->name('fixtures.events');
});
