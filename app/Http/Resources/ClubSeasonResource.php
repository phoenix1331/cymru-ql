<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ClubSeason;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClubSeason
 */
final class ClubSeasonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'season' => ['slug' => $this->season->slug, 'name' => $this->season->name],
            'league' => ['slug' => $this->league->slug, 'name' => $this->league->name, 'tier' => $this->league->tier],
            'position' => $this->position,
        ];
    }
}
