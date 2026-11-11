<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LeagueDistancesRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'league' => ['required', 'string', 'exists:leagues,slug'],
            'season' => ['nullable', 'string', 'exists:seasons,slug'],
        ];
    }
}
