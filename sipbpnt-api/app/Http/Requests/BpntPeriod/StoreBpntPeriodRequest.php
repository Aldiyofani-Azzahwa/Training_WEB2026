<?php

declare(strict_types=1);

namespace App\Http\Requests\BpntPeriod;

use Illuminate\Foundation\Http\FormRequest;

class StoreBpntPeriodRequest
    extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9._-]+$/',
                'unique:bpnt_periods,code',
            ],

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

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex'
                => 'Kode periode hanya boleh '
                    .'berisi huruf, angka, titik, '
                    .'garis bawah, dan tanda hubung.',

            'code.unique'
                => 'Kode periode sudah digunakan.',
        ];
    }
}