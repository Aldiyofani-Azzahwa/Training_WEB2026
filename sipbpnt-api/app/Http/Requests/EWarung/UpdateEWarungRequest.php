<?php

declare(strict_types=1);

namespace App\Http\Requests\EWarung;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEWarungRequest
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
        $eWarungId =
            (int) $this->route(
                'eWarung'
            );

        return [
            'name' => [
                'required',
                'string',
                'max:150',

                Rule::unique(
                    'e_warungs',
                    'name'
                )->ignore(
                    $eWarungId
                ),
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