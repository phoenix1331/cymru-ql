<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListClubsRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'league' => ['nullable', 'string', 'exists:leagues,slug'],
            'season' => ['nullable', 'string', 'exists:seasons,slug'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
