<?php

declare(strict_types=1);

use App\GraphQL\ExampleQueries;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->seed();
});

it('runs every example query without errors', function () {
    $examples = app(ExampleQueries::class)->all();

    expect($examples)->toHaveCount(5);

    foreach ($examples as $example) {
        $headers = json_decode($example['headers'] ?? '{}', true);

        $this->graphQL($example['query'], [], [], $headers)
            ->assertOk()
            ->assertJsonMissingPath('errors');
    }
});

it('turns a headers comment into tab headers', function () {
    $directory = sys_get_temp_dir().'/cymruql-examples-'.uniqid();
    (new Filesystem)->ensureDirectoryExists($directory);
    file_put_contents("{$directory}/b.graphql", "# headers: {\"X-Batchload\": \"off\"}\n{ seasons { slug } }\n");
    file_put_contents("{$directory}/a.graphql", "{ leagues { slug } }\n");

    expect((new ExampleQueries($directory))->all())->toBe([
        ['query' => "{ leagues { slug } }\n"],
        ['query' => "{ seasons { slug } }\n", 'headers' => "{\n    \"X-Batchload\": \"off\"\n}"],
    ])->and((new ExampleQueries("{$directory}/missing"))->all())->toBe([]);

    (new Filesystem)->deleteDirectory($directory);
});

it('serves graphiql with the examples preloaded', function () {
    $this->get('/graphiql')
        ->assertOk()
        ->assertSee('CymruQL GraphiQL', false)
        ->assertSee('ClubShowcase', false)
        ->assertSee('defaultTabs', false);
});
