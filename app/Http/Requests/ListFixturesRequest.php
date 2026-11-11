<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\FixtureStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListFixturesRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'season' => ['nullable', 'string', 'exists:seasons,slug'],
            'league' => ['nullable', 'string', 'exists:leagues,slug'],
            'club' => ['nullable', 'string', 'exists:clubs,slug'],
            'status' => ['nullable', Rule::enum(FixtureStatus::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'round' => ['nullable', 'integer', 'between:1,99'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
