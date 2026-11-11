<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MatchEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MatchEvent
 */
final class MatchEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'minute' => $this->minute,
            'club' => $this->club === null ? null : ['slug' => $this->club->slug, 'name' => $this->club->name],
            'score' => ['home' => $this->home_score, 'away' => $this->away_score],
            'description' => $this->description,
        ];
    }
}
