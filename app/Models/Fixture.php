<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FixtureStatus;
use Database\Factories\FixtureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $season_id
 * @property int $league_id
 * @property int $home_club_id
 * @property int $away_club_id
 * @property int $ground_id
 * @property int $round
 * @property Carbon $kicks_off_at
 * @property FixtureStatus $status
 * @property int|null $home_score
 * @property int|null $away_score
 * @property int|null $minute
 * @property-read Season $season
 * @property-read League $league
 * @property-read Club $homeClub
 * @property-read Club $awayClub
 * @property-read Ground $ground
 */
#[Fillable([
    'season_id', 'league_id', 'home_club_id', 'away_club_id', 'ground_id',
    'round', 'kicks_off_at', 'status', 'home_score', 'away_score', 'minute',
])]
class Fixture extends Model
{
    /** @use HasFactory<FixtureFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Season, $this>
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * @return BelongsTo<League, $this>
     */
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    /**
     * @return BelongsTo<Club, $this>
     */
    public function homeClub(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'home_club_id');
    }

    /**
     * @return BelongsTo<Club, $this>
     */
    public function awayClub(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'away_club_id');
    }

    /**
     * @return BelongsTo<Ground, $this>
     */
    public function ground(): BelongsTo
    {
        return $this->belongsTo(Ground::class);
    }

    /**
     * @return HasMany<MatchEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(MatchEvent::class)->orderBy('minute')->orderBy('id');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function finished(Builder $query): void
    {
        $query->where('status', FixtureStatus::Finished);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kicks_off_at' => 'datetime',
            'status' => FixtureStatus::class,
            'round' => 'integer',
            'home_score' => 'integer',
            'away_score' => 'integer',
            'minute' => 'integer',
        ];
    }
}
