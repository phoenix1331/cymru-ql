<?php

declare(strict_types=1);

use App\Models\Club;
use App\Models\Ground;

it('shows a ground with the clubs that play there', function () {
    $ground = Ground::factory()->create([
        'slug' => 'centre-for-sporting-excellence-ystrad-mynach',
        'name' => 'Centre for Sporting Excellence',
        'town' => 'Ystrad Mynach',
        'latitude' => 51.63497,
        'longitude' => -3.23738,
        'capacity' => 1000,
        'opened' => null,
        'address' => 'Caerphilly Road, Ystrad Mynach',
        'description' => 'Shared ground.',
    ]);
    Club::factory()->for($ground)->create(['slug' => 'trethomas-bluebirds', 'name' => 'Trethomas Bluebirds']);
    Club::factory()->for($ground)->create(['slug' => 'caerphilly-athletic', 'name' => 'Caerphilly Athletic']);

    $this->getJson("/api/grounds/{$ground->slug}")
        ->assertOk()
        ->assertHeader('X-Query-Count', '2')
        ->assertJsonPath('data.latitude', 51.63497)
        ->assertJsonPath('data.capacity', 1000)
        ->assertJsonPath('data.opened', null)
        ->assertJsonPath('data.clubs.*.slug', ['trethomas-bluebirds', 'caerphilly-athletic'])
        ->assertJsonPath('data.clubs.0.link', url('/api/clubs/trethomas-bluebirds'))
        ->assertJsonPath('data.links.self', url("/api/grounds/{$ground->slug}"));
});

it('lists grounds alphabetically', function () {
    Ground::factory()->create(['name' => 'The Oval']);
    Ground::factory()->create(['name' => 'Jenner Park']);

    $this->getJson('/api/grounds')
        ->assertOk()
        ->assertJsonPath('data.*.name', ['Jenner Park', 'The Oval'])
        ->assertJsonMissingPath('data.0.clubs');
});

it('validates page size and returns 404 for unknown grounds', function () {
    $this->getJson('/api/grounds?per_page=0')->assertUnprocessable()->assertJsonValidationErrors('per_page');
    $this->getJson('/api/grounds/nowhere')->assertNotFound();
});
