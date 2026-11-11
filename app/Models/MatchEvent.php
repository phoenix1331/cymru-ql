<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MatchEventType;
use Database\Factories\MatchEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Something that happened in a fixture, with the score after it.
 *
 * @property int $id
 * @property int $fixture_id
 * @property int|null $club_id
 * @property MatchEventType $type
 * @property int $minute
 * @property int $home_score
 * @property int $away_score
 * @property string|null $description
 * @property-read Fixture $fixture
 * @property-read Club|null $club
 */
#[Fillable(['fixture_id', 'club_id', 'type', 'minute', 'home_score', 'away_score', 'description'])]
class MatchEvent extends Model
{
    /** @use HasFactory<MatchEventFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Fixture, $this>
     */
    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }

    /**
     * @return BelongsTo<Club, $this>
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MatchEventType::class,
            'minute' => 'integer',
            'home_score' => 'integer',
            'away_score' => 'integer',
        ];
    }
}
