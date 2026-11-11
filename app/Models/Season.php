<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SeasonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property bool $is_current
 */
#[Fillable(['slug', 'name', 'starts_on', 'ends_on', 'is_current'])]
class Season extends Model
{
    /** @use HasFactory<SeasonFactory> */
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
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function current(Builder $query): void
    {
        $query->where('is_current', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
        ];
    }
}
