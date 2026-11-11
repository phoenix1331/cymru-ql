<?php

declare(strict_types=1);

namespace App\GraphQL\Validation;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Introspection;
use GraphQL\Validator\Rules\QueryDepth;

/**
 * The standard depth rule, minus introspection. Tools such as GraphiQL and
 * graphql-codegen send an introspection query nested far deeper than any
 * real query should be; complexity already ignores it, now depth does too.
 */
final class QueryDepthExcludingIntrospection extends QueryDepth
{
    protected function nodeDepth(Node $node, int $depth = 0, int $maxDepth = 0): int
    {
        if ($node instanceof FieldNode && in_array($node->name->value, [Introspection::SCHEMA_FIELD_NAME, Introspection::TYPE_FIELD_NAME], true)) {
            return $maxDepth;
        }

        return parent::nodeDepth($node, $depth, $maxDepth);
    }
}
