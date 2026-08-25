<?php

declare(strict_types=1);

namespace App\Http\Requests\Surveyor;

use Illuminate\Foundation\Http\FormRequest;

class ListSurveyorParticipantRequest
    extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:150',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'between:1,50',
            ],
        ];
    }
}