<?php

declare(strict_types=1);

namespace App\Data;

use InvalidArgumentException;

/**
 * Helpers for reading loosely typed rows decoded from the JSON seed files.
 */
final class Row
{
    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $keys
     */
    public static function require(array $row, array $keys): void
    {
        $missing = array_values(array_filter(
            $keys,
            fn (string $key): bool => ! array_key_exists($key, $row) || $row[$key] === null,
        ));

        if ($missing === []) {
            return;
        }

        throw new InvalidArgumentException('Data row is missing required keys: '.implode(', ', $missing));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function nullableInt(array $row, string $key): ?int
    {
        $value = $row[$key] ?? null;

        return $value === null ? null : (int) $value;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function nullableString(array $row, string $key): ?string
    {
        $value = $row[$key] ?? null;

        return $value === null || $value === '' ? null : (string) $value;
    }
}
