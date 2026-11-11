<?php

declare(strict_types=1);

use App\Data\ClubData;
use App\Data\GroundData;
use App\Import\Overrides;

it('replaces club and ground fields by slug', function () {
    $overrides = Overrides::fromArray([
        'clubs' => ['rhyl' => ['founded' => 2020, 'slug' => 'ignored']],
        'grounds' => ['belle-vue-rhyl' => ['address' => 'Grange Road, Rhyl']],
    ]);

    $clubs = $overrides->applyToClubs([
        new ClubData('rhyl', 'Rhyl', 'belle-vue-rhyl', '#fff', founded: 1883),
        new ClubData('bala-town', 'Bala Town', 'maes-tegid-bala', '#fff', founded: 1880),
    ]);
    $grounds = $overrides->applyToGrounds([new GroundData('belle-vue-rhyl', 'Belle Vue', 'Rhyl', 53.3, -3.4)]);

    expect($clubs[0]->founded)->toBe(2020)
        ->and($clubs[0]->slug)->toBe('rhyl')
        ->and($clubs[1]->founded)->toBe(1880)
        ->and($grounds[0]->address)->toBe('Grange Road, Rhyl');
});

it('does nothing when empty', function () {
    $club = new ClubData('rhyl', 'Rhyl', 'belle-vue-rhyl', '#fff');

    expect((new Overrides)->applyToClubs([$club]))->toBe([$club])
        ->and(Overrides::fromArray([])->applyToGrounds([]))->toBe([]);
});
