<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Distance;
use App\Models\Ground;
use App\Models\League;
use App\Models\Season;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class GroundService
{
    /**
     * @return LengthAwarePaginator<int, Ground>
     */
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return Ground::query()->orderBy('name')->paginate($perPage);
    }

    public function find(string $slug): Ground
    {
        return Ground::query()->where('slug', $slug)->firstOrFail();
    }

    /**
     * The closest other grounds by road, optionally only those used by clubs
     * in one league in one season.
     *
     * @return Collection<int, Distance>
     */
    public function nearby(Ground $ground, int $limit = 5, ?League $league = null, ?Season $season = null): Collection
    {
        return $this->distancesFrom($ground, $league, $season)
            ->orderBy('road_km')
            ->limit($limit)
            ->get();
    }

    /**
     * Distances from the ground to every ground in a league for a season,
     * nearest first. This is what a club travels to for its away games.
     *
     * @return Collection<int, Distance>
     */
    public function distancesInLeague(Ground $ground, League $league, Season $season): Collection
    {
        return $this->distancesFrom($ground, $league, $season)->orderBy('road_km')->get();
    }

    public function distanceBetween(Ground $from, Ground $to): Distance
    {
        return Distance::query()
            ->whereBelongsTo($from, 'fromGround')
            ->whereBelongsTo($to, 'toGround')
            ->firstOrFail();
    }

    /**
     * @return Builder<Distance>
     */
    private function distancesFrom(Ground $ground, ?League $league, ?Season $season): Builder
    {
        return Distance::query()
            ->whereBelongsTo($ground, 'fromGround')
            ->when($league !== null && $season !== null, fn (Builder $query) => $query->whereHas(
                'toGround.clubs.clubSeasons',
                fn (Builder $memberships) => $memberships->whereBelongsTo($league)->whereBelongsTo($season),
            ));
    }
}
