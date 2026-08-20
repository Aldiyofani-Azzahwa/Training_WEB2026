<?php

declare(strict_types=1);

namespace App\Http\Requests\Surveyor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSurveyorStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }
}