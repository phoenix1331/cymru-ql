<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

it('lets subscriber collections survive the database cache', function () {
    Cache::store('database')->put('lighthouse-topic', collect(['subscriber']), 60);

    expect(Cache::store('database')->get('lighthouse-topic'))->toBeInstanceOf(Collection::class);
});

it('still refuses to unserialise other classes', function () {
    Cache::store('database')->put('other', new ArrayObject([1]), 60);

    expect(Cache::store('database')->get('other'))->toBeInstanceOf(__PHP_Incomplete_Class::class);
});
