<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ClubController;
use App\Http\Controllers\Api\GroundController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::get('clubs', [ClubController::class, 'index'])->name('clubs.index');
    Route::get('clubs/{club}', [ClubController::class, 'show'])->name('clubs.show');
    Route::get('clubs/{club}/seasons', [ClubController::class, 'seasons'])->name('clubs.seasons');

    Route::get('grounds', [GroundController::class, 'index'])->name('grounds.index');
    Route::get('grounds/{ground}', [GroundController::class, 'show'])->name('grounds.show');
});
