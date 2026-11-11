<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GroundFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $town
 * @property float $latitude
 * @property float $longitude
 * @property int|null $capacity
 * @property int|null $opened
 * @property string|null $address
 * @property string|null $description
 */
#[Fillable(['slug', 'name', 'town', 'latitude', 'longitude', 'capacity', 'opened', 'address', 'description'])]
class Ground extends Model
{
    /** @use HasFactory<GroundFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<Club, $this>
     */
    public function clubs(): HasMany
    {
        return $this->hasMany(Club::class);
    }

    /**
     * Distances from this ground to every other ground.
     *
     * @return HasMany<Distance, $this>
     */
    public function distances(): HasMany
    {
        return $this->hasMany(Distance::class, 'from_ground_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'capacity' => 'integer',
            'opened' => 'integer',
        ];
    }
}
