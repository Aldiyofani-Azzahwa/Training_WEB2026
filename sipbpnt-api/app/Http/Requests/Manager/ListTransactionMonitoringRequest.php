<?php

declare(strict_types=1);

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

final class ListTransactionMonitoringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

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

            'e_warung_id' => [
                'nullable',
                'integer',
                'exists:e_warungs,id',
            ],

            'surveyor_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'outside_assignment' => [
                'nullable',
                'boolean',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'between:1,50',
            ],
        ];
    }
}