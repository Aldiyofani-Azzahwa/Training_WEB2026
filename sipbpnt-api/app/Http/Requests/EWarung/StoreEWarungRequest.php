<?php

declare(strict_types=1);

namespace App\Http\Requests\EWarung;

use Illuminate\Foundation\Http\FormRequest;

class StoreEWarungRequest
    extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'
                => trim(
                    (string) $this->input(
                        'name',
                        ''
                    )
                ),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
                'unique:e_warungs,name',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'
                => 'Nama E-Warung wajib diisi.',

            'name.max'
                => 'Nama E-Warung maksimal 150 karakter.',

            'name.unique'
                => 'Nama E-Warung sudah terdaftar.',
        ];
    }
}