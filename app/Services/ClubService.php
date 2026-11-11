<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Club;
use App\Models\ClubSeason;
use App\Models\League;
use App\Models\Season;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class ClubService
{
    /**
     * Clubs alphabetically, optionally limited to one league in one season.
     *
     * @return LengthAwarePaginator<int, Club>
     */
    public function paginate(?League $league = null, ?Season $season = null, int $perPage = 20): LengthAwarePaginator
    {
        return Club::query()
            ->when($league !== null && $season !== null, fn ($query) => $query->whereHas(
                'clubSeasons',
                fn ($memberships) => $memberships->whereBelongsTo($league)->whereBelongsTo($season),
            ))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function find(string $slug): Club
    {
        return Club::query()->where('slug', $slug)->firstOrFail();
    }

    /**
     * Every season the club has played in the top two tiers, newest first.
     *
     * @return Collection<int, ClubSeason>
     */
    public function history(Club $club): Collection
    {
        return $club->clubSeasons()
            ->with(['league', 'season'])
            ->join('seasons', 'seasons.id', '=', 'club_seasons.season_id')
            ->orderByDesc('seasons.starts_on')
            ->select('club_seasons.*')
            ->get();
    }

    public function membership(Club $club, Season $season): ?ClubSeason
    {
        return $club->clubSeasons()->with('league')->whereBelongsTo($season)->first();
    }
}
