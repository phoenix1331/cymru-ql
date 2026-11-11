<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// The React app handles every page route; API, GraphQL and tooling routes are
// registered elsewhere and excluded here.
Route::view('/{path?}', 'app')
    ->where('path', '^(?!api(/|$)|graphql(/|$)|graphiql(/|$)|up$).*')
    ->name('app');
