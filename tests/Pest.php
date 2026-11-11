<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Nuwave\Lighthouse\Testing\RefreshesSchemaCache;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class, MakesGraphQLRequests::class, RefreshesSchemaCache::class)
    ->beforeEach(fn () => Http::preventStrayRequests())
    ->in('Feature');
