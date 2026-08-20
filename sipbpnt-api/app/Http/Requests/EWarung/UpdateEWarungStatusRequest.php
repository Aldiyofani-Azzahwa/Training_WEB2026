<?php

declare(strict_types=1);

namespace App\Http\Requests\EWarung;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEWarungStatusRequest
    extends FormRequest
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