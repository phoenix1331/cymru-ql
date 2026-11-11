<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ClubSeasonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A club's league membership for one season, with its final position once
 * the season is complete.
 *
 * @property int $id
 * @property int $club_id
 * @property int $season_id
 * @property int $league_id
 * @property int|null $position
 * @property-read Club $club
 * @property-read Season $season
 * @property-read League $league
 */
#[Fillable(['club_id', 'season_id', 'league_id', 'position'])]
class ClubSeason extends Model
{
    /** @use HasFactory<ClubSeasonFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Club, $this>
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['position' => 'integer'];
    }
}
