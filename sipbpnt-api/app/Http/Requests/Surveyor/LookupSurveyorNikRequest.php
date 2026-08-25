<?php

declare(strict_types=1);

namespace App\Http\Requests\Surveyor;

use Illuminate\Foundation\Http\FormRequest;

class LookupSurveyorNikRequest
    extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $nik =
            preg_replace(
                '/\D+/',
                '',
                (string) $this->input(
                    'nik',
                    ''
                )
            );

        $this->merge([
            'nik'
                => $nik,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nik' => [
                'required',
                'string',
                'regex:/^\d{16}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nik.required'
                => 'NIK wajib diisi.',

            'nik.regex'
                => 'NIK harus terdiri dari 16 digit angka.',
        ];
    }
}