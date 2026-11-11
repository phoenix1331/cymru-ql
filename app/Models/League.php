<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LeagueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property int $tier
 * @property string $region
 */
#[Fillable(['slug', 'name', 'tier', 'region'])]
class League extends Model
{
    /** @use HasFactory<LeagueFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<ClubSeason, $this>
     */
    public function clubSeasons(): HasMany
    {
        return $this->hasMany(ClubSeason::class);
    }

    /**
     * Clubs that have played in this league in any season.
     *
     * @return BelongsToMany<Club, $this>
     */
    public function clubs(): BelongsToMany
    {
        return $this->belongsToMany(Club::class, 'club_seasons')
            ->withPivot(['season_id', 'position'])
            ->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['tier' => 'integer'];
    }
}
