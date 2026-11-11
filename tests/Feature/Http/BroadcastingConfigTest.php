<?php

declare(strict_types=1);

it('broadcasts lighthouse subscriptions through reverb by default', function () {
    $lighthouse = require config_path('lighthouse.php');

    expect(config('broadcasting.connections.reverb.driver'))->toBe('reverb')
        ->and($lighthouse['subscriptions']['broadcasters']['reverb']['connection'])->toBe('reverb');
});

it('registers the reverb server command', function () {
    $this->artisan('list', ['namespace' => 'reverb'])->expectsOutputToContain('reverb:start')->assertSuccessful();
});
