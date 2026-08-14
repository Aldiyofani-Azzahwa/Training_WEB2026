<?php

declare(strict_types=1);

namespace App\Http\Requests\Bnba;

use Illuminate\Foundation\Http\FormRequest;

class StoreBnbaImportRequest
    extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period_id' => [
                'required',
                'integer',
                'exists:bpnt_periods,id',
            ],

            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:xlsx,xls',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max'
                => 'Ukuran file BNBA '
                    .'maksimal 10 MB.',

            'file.mimes'
                => 'File BNBA harus '
                    .'berformat Excel '
                    .'.xlsx atau .xls.',
        ];
    }
}