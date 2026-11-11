<?php

declare(strict_types=1);

namespace App\GraphQL\Validation;

use GraphQL\Validator\Rules\QueryDepth;
use Nuwave\Lighthouse\Execution\CacheableValidationRulesProvider;

/**
 * Lighthouse's validation rules with the depth limit swapped for one that
 * does not count introspection queries.
 */
final class ValidationRulesProvider extends CacheableValidationRulesProvider
{
    public function cacheableValidationRules(): array
    {
        return [
            ...parent::cacheableValidationRules(),
            QueryDepth::class => new QueryDepthExcludingIntrospection(
                (int) $this->configRepository->get('lighthouse.security.max_query_depth', 0),
            ),
        ];
    }
}
