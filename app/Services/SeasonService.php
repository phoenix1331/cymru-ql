<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Season;
use Illuminate\Database\Eloquent\Collection;

final class SeasonService
{
    /**
     * @return Collection<int, Season>
     */
    public function all(): Collection
    {
        return Season::query()->orderByDesc('starts_on')->get();
    }

    public function current(): Season
    {
        return Season::query()->current()->firstOrFail();
    }

    /**
     * The season with the given slug, or the current season when none is given.
     */
    public function resolve(?string $slug): Season
    {
        if ($slug === null || $slug === '') {
            return $this->current();
        }

        return Season::query()->where('slug', $slug)->firstOrFail();
    }
}
