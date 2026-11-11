<?php

declare(strict_types=1);

namespace App\GraphQL\Exceptions;

use GraphQL\Error\ClientAware;
use RuntimeException;

/**
 * A lookup by slug found nothing. Safe to show to API clients.
 */
final class NotFound extends RuntimeException implements ClientAware
{
    public static function slug(string $type, string $slug): self
    {
        return new self("{$type} \"{$slug}\" not found.");
    }

    public function isClientSafe(): bool
    {
        return true;
    }
}
