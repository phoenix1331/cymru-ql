<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\League;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin League
 */
final class LeagueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'tier' => $this->tier,
            'region' => $this->region,
            'links' => [
                'self' => route('api.leagues.show', $this->slug),
                'table' => route('api.leagues.table', $this->slug),
                'clubs' => route('api.clubs.index', ['league' => $this->slug]),
            ],
        ];
    }
}
