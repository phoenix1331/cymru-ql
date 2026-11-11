<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Club;
use App\Models\Fixture;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Fixture
 */
final class FixtureResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'round' => $this->round,
            'kicks_off_at' => $this->kicks_off_at->toIso8601String(),
            'status' => $this->status->value,
            'minute' => $this->minute,
            'home' => $this->club($this->homeClub),
            'away' => $this->club($this->awayClub),
            'score' => $this->home_score === null ? null : ['home' => $this->home_score, 'away' => $this->away_score],
            'ground' => ['slug' => $this->ground->slug, 'name' => $this->ground->name],
            'links' => [
                'self' => route('api.fixtures.show', $this->id),
                'events' => route('api.fixtures.events', $this->id),
            ],
        ];
    }

    /**
     * @return array{slug: string, name: string, link: string}
     */
    private function club(Club $club): array
    {
        return ['slug' => $club->slug, 'name' => $club->name, 'link' => route('api.clubs.show', $club->slug)];
    }
}
