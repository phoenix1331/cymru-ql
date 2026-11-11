<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Distance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Distance
 */
final class DistanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'from' => $this->whenLoaded('fromGround', fn (): array => [
                'slug' => $this->fromGround->slug,
                'name' => $this->fromGround->name,
                'link' => route('api.grounds.show', $this->fromGround->slug),
            ]),
            'to' => [
                'slug' => $this->toGround->slug,
                'name' => $this->toGround->name,
                'town' => $this->toGround->town,
                'latitude' => $this->toGround->latitude,
                'longitude' => $this->toGround->longitude,
                'link' => route('api.grounds.show', $this->toGround->slug),
            ],
            'road_km' => $this->road_km,
            'straight_km' => $this->straight_km,
        ];
    }
}
