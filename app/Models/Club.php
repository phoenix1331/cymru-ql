<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ClubFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property int $ground_id
 * @property string $colour
 * @property string|null $nickname
 * @property int|null $founded
 * @property-read Ground $ground
 */
#[Fillable(['slug', 'name', 'ground_id', 'colour', 'nickname', 'founded'])]
class Club extends Model
{
    /** @use HasFactory<ClubFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Ground, $this>
     */
    public function ground(): BelongsTo
    {
        return $this->belongsTo(Ground::class);
    }

    /**
     * @return HasMany<ClubSeason, $this>
     */
    public function clubSeasons(): HasMany
    {
        return $this->hasMany(ClubSeason::class);
    }

    /**
     * @return BelongsToMany<Season, $this>
     */
    public function seasons(): BelongsToMany
    {
        return $this->belongsToMany(Season::class, 'club_seasons')
            ->withPivot(['league_id', 'position'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Fixture, $this>
     */
    public function homeFixtures(): HasMany
    {
        return $this->hasMany(Fixture::class, 'home_club_id');
    }

    /**
     * @return HasMany<Fixture, $this>
     */
    public function awayFixtures(): HasMany
    {
        return $this->hasMany(Fixture::class, 'away_club_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['founded' => 'integer'];
    }
}
