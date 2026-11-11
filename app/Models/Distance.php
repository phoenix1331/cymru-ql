<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DistanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Directed distance between two grounds. Road distance can differ by
 * direction (one-way systems); straight line distance does not.
 *
 * @property int $id
 * @property int $from_ground_id
 * @property int $to_ground_id
 * @property float $road_km
 * @property float $straight_km
 * @property-read Ground $fromGround
 * @property-read Ground $toGround
 */
#[Fillable(['from_ground_id', 'to_ground_id', 'road_km', 'straight_km'])]
class Distance extends Model
{
    /** @use HasFactory<DistanceFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Ground, $this>
     */
    public function fromGround(): BelongsTo
    {
        return $this->belongsTo(Ground::class, 'from_ground_id');
    }

    /**
     * @return BelongsTo<Ground, $this>
     */
    public function toGround(): BelongsTo
    {
        return $this->belongsTo(Ground::class, 'to_ground_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'road_km' => 'float',
            'straight_km' => 'float',
        ];
    }
}
