<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Club;
use App\Models\Ground;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ground
 */
final class GroundResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'town' => $this->town,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'capacity' => $this->capacity,
            'opened' => $this->opened,
            'address' => $this->address,
            'description' => $this->description,
            'clubs' => $this->whenLoaded('clubs', fn (): array => $this->clubs->map(fn (Club $club): array => [
                'slug' => $club->slug,
                'name' => $club->name,
                'link' => route('api.clubs.show', $club->slug),
            ])->all()),
            'links' => [
                'self' => route('api.grounds.show', $this->slug),
                'nearby' => route('api.grounds.nearby', $this->slug),
            ],
        ];
    }
}
