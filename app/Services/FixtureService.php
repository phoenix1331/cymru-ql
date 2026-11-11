<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Fixture;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class FixtureService
{
    /**
     * @return LengthAwarePaginator<int, Fixture>
     */
    public function paginate(FixtureFilters $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage);
    }

    public function find(int $id): Fixture
    {
        return Fixture::query()->findOrFail($id);
    }

    /**
     * @return Builder<Fixture>
     */
    public function query(FixtureFilters $filters): Builder
    {
        return Fixture::query()
            ->whereBelongsTo($filters->season)
            ->when($filters->league, fn (Builder $query, $league) => $query->whereBelongsTo($league))
            ->when($filters->club, fn (Builder $query, $club) => $query->where(
                fn (Builder $either) => $either->where('home_club_id', $club->id)->orWhere('away_club_id', $club->id),
            ))
            ->when($filters->status, fn (Builder $query, $status) => $query->where('status', $status))
            ->when($filters->from, fn (Builder $query, $from) => $query->where('kicks_off_at', '>=', $from))
            ->when($filters->to, fn (Builder $query, $to) => $query->where('kicks_off_at', '<=', $to))
            ->when($filters->round, fn (Builder $query, $round) => $query->where('round', $round))
            ->orderBy('kicks_off_at')
            ->orderBy('id');
    }
}
