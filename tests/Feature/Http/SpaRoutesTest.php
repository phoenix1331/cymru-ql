<?php

declare(strict_types=1);

it('serves the react app for page routes', function (string $path) {
    $this->get($path)
        ->assertOk()
        ->assertSee('<div id="app"></div>', false)
        ->assertSee('<title>CymruQL</title>', false);
})->with(['/', '/leagues/cymru-premier', '/clubs/caernarfon-town', '/grounds/the-oval-caernarfon', '/anything/else']);

it('leaves api and graphql routes alone', function () {
    $this->getJson('/api/nope')->assertNotFound()->assertJsonStructure(['message']);
    $this->get('/up')->assertOk()->assertDontSee('<div id="app"></div>', false);
});
