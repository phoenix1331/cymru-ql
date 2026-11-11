<?php

declare(strict_types=1);

use App\Models\Club;
use App\Models\Season;

it('allows the showcase query', function () {
    Club::factory()->create(['slug' => 'bala-town']);
    Season::factory()->current()->create();

    $this->graphQL('{
        club(slug: "bala-town") {
            name
            ground { name }
            league { league { name } table { position club { name ground { name } } points form } }
            awayTrips { roadKm to { name clubs { name } } }
        }
    }')->assertOk()->assertJsonMissingPath('errors');
});

it('rejects queries nested too deeply', function () {
    $this->graphQL('{ clubs(first: 2) { data { ground { clubs { ground { clubs { ground { clubs { ground { clubs { name } } } } } } } } } } }')
        ->assertGraphQLErrorMessage('Max query depth should be 8 but got 9.');
});

it('rejects queries that would be too expensive', function () {
    $this->graphQL('{ clubs(first: 100) { data { name nickname colour founded ground { name town capacity clubs { name slug } } } } }')
        ->assertGraphQLErrorMessage('Max query complexity should be 1000 but got 1201.');
});

it('still allows introspection for tooling', function () {
    $this->graphQL('
        query IntrospectionQuery {
            __schema {
                queryType { name }
                types { kind name fields(includeDeprecated: true) { name args { name type { ...TypeRef } } type { ...TypeRef } } }
            }
        }
        fragment TypeRef on __Type {
            kind name
            ofType { kind name ofType { kind name ofType { kind name ofType { kind name ofType { kind name ofType { kind name ofType { kind name } } } } } } }
        }
    ')->assertOk()->assertJsonPath('data.__schema.queryType.name', 'Query');

    $this->graphQL('{ __type(name: "Club") { fields { type { ofType { ofType { ofType { ofType { ofType { ofType { ofType { ofType { name } } } } } } } } } } } }')
        ->assertOk()
        ->assertJsonMissingPath('errors');
});
