<?php

declare(strict_types=1);

namespace App\Http\Requests\HeadOffice;

use Illuminate\Foundation\Http\FormRequest;

final class ShowHeadOfficeDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kecamatan_id' => $this->filled('kecamatan_id')
                ? $this->input('kecamatan_id')
                : null,
            'kelurahan_id' => $this->filled('kelurahan_id')
                ? $this->input('kelurahan_id')
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'kecamatan_id' => [
                'nullable',
                'integer',
                'exists:kecamatans,id',
            ],
            'kelurahan_id' => [
                'nullable',
                'integer',
                'exists:kelurahans,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'kecamatan_id.integer'
                => 'Kecamatan tidak valid.',
            'kecamatan_id.exists'
                => 'Kecamatan tidak ditemukan.',
            'kelurahan_id.integer'
                => 'Kelurahan tidak valid.',
            'kelurahan_id.exists'
                => 'Kelurahan tidak ditemukan.',
        ];
    }
}