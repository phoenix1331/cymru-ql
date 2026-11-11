<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Resolves slugs from the JSON seed data to database ids, failing loudly on
 * a reference to something that does not exist.
 */
final readonly class SlugLookup
{
    /**
     * @param  array<string, int>  $ids
     */
    private function __construct(private string $label, private array $ids) {}

    /**
     * @param  class-string<Model>  $model
     */
    public static function for(string $model, string $label): self
    {
        /** @var array<string, int> $ids */
        $ids = $model::query()->pluck('id', 'slug')->all();

        return new self($label, $ids);
    }

    public function id(string $slug): int
    {
        return $this->ids[$slug] ?? throw new RuntimeException("Seed data refers to unknown {$this->label} \"{$slug}\".");
    }
}
