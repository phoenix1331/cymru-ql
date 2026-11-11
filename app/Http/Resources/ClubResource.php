<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Club
 */
final class ClubResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'colour' => $this->colour,
            'founded' => $this->founded,
            'ground' => $this->whenLoaded(
                'ground',
                fn (): array => ['slug' => $this->ground->slug, 'name' => $this->ground->name],
            ),
            'links' => [
                'self' => route('api.clubs.show', $this->slug),
                'seasons' => route('api.clubs.seasons', $this->slug),
                'ground' => $this->whenLoaded('ground', fn (): string => route('api.grounds.show', $this->ground->slug)),
            ],
        ];
    }
}
