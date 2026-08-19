<?php

declare(strict_types=1);

namespace App\Http\Requests\BpntPeriod;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBpntPeriodRequest
    extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'year' => [
                'required',
                'integer',
                'between:2000,2100',
            ],
        ];
    }
}