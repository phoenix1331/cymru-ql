<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\TableRow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property TableRow $resource
 */
final class TableRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $row = $this->resource;

        return [
            'position' => $row->position,
            'club' => [
                'slug' => $row->club->slug,
                'name' => $row->club->name,
                'colour' => $row->club->colour,
                'link' => route('api.clubs.show', $row->club->slug),
            ],
            'played' => $row->played,
            'won' => $row->won,
            'drawn' => $row->drawn,
            'lost' => $row->lost,
            'goals_for' => $row->goalsFor,
            'goals_against' => $row->goalsAgainst,
            'goal_difference' => $row->goalDifference(),
            'points' => $row->points,
            'form' => $row->form,
        ];
    }
}
