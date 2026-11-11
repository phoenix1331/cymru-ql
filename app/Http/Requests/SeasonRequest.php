<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SeasonRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'season' => ['nullable', 'string', 'exists:seasons,slug'],
        ];
    }

    public function seasonSlug(): ?string
    {
        return $this->filled('season') ? $this->string('season')->value() : null;
    }
}
