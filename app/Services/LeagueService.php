<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Club;
use App\Models\League;
use App\Models\Season;
use Illuminate\Database\Eloquent\Collection;

final class LeagueService
{
    /**
     * @return Collection<int, League>
     */
    public function all(): Collection
    {
        return League::query()->orderBy('tier')->orderBy('name')->get();
    }

    public function find(string $slug): League
    {
        return League::query()->where('slug', $slug)->firstOrFail();
    }

    /**
     * Clubs playing in the league in the given season, alphabetically.
     *
     * @return Collection<int, Club>
     */
    public function clubs(League $league, Season $season): Collection
    {
        return Club::query()
            ->whereHas('clubSeasons', fn ($query) => $query->whereBelongsTo($league)->whereBelongsTo($season))
            ->orderBy('name')
            ->get();
    }
}
