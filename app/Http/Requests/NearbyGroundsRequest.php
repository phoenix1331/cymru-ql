<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class NearbyGroundsRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'between:1,50'],
            'league' => ['nullable', 'string', 'exists:leagues,slug'],
            'season' => ['nullable', 'string', 'exists:seasons,slug'],
        ];
    }
}
