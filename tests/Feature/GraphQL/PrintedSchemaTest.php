<?php

declare(strict_types=1);

use GraphQL\Utils\SchemaPrinter;
use Nuwave\Lighthouse\Schema\SchemaBuilder;

it('keeps the schema used by graphql-codegen in sync with lighthouse', function () {
    $printed = SchemaPrinter::doPrint(app(SchemaBuilder::class)->schema());

    expect(file_get_contents(resource_path('js/graphql/lighthouse-schema.graphql')))
        ->toBe($printed, 'resources/js/graphql/lighthouse-schema.graphql is stale. Run npm run codegen.');
});
