<?php

declare(strict_types=1);

namespace App\Http\Requests\Surveyor;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSurveyorMonitoringReportRequest
    extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $commodities = $this->input(
            'commodities',
            []
        );

        $this->merge([
            'commodities' => is_array($commodities)
                ? array_map(
                    fn ($commodity): string =>
                        trim((string) $commodity),
                    $commodities
                )
                : $commodities,

            'social_officer_name' => $this->filled(
                'social_officer_name'
            )
                ? trim(
                    (string) $this->input(
                        'social_officer_name'
                    )
                )
                : null,

            'distribution_assistant_name' => $this->filled(
                'distribution_assistant_name'
            )
                ? trim(
                    (string) $this->input(
                        'distribution_assistant_name'
                    )
                )
                : null,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'commodities' => [
                'required',
                'array',
                'min:1',
                'max:5',
            ],

            'commodities.*' => [
                'required',
                'string',
                'max:100',
                'distinct:ignore_case',
            ],

            'social_officer_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'distribution_assistant_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            /*
             * Seluruh field berikut diperoleh
             * dari periode aktif, assignment,
             * BNBA, transaksi dan verifikasi.
             */
            'period_id' => [
                'prohibited',
            ],

            'assignment_id' => [
                'prohibited',
            ],

            'surveyor_id' => [
                'prohibited',
            ],

            'kelurahan_id' => [
                'prohibited',
            ],

            'summary' => [
                'prohibited',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'commodities.required'
                => 'Minimal satu komoditas wajib diisi.',

            'commodities.min'
                => 'Minimal satu komoditas wajib diisi.',

            'commodities.max'
                => 'Komoditas maksimal lima jenis.',

            'commodities.*.required'
                => 'Nama komoditas tidak boleh kosong.',

            'commodities.*.distinct'
                => 'Nama komoditas tidak boleh sama.',

            'commodities.*.max'
                => 'Nama komoditas maksimal 100 karakter.',

            '*.prohibited'
                => 'Field :attribute dihitung otomatis dan tidak boleh dikirim.',
        ];
    }
}