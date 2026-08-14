<?php

declare(strict_types=1);

namespace App\Http\Requests\Bnba;

use Illuminate\Foundation\Http\FormRequest;

class ListBnbaParticipantRequest
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

            'search' => [
                'nullable',
                'string',
                'max:150',
            ],

            'kecamatan' => [
                'nullable',
                'string',
                'max:100',
            ],

            'kelurahan' => [
                'nullable',
                'string',
                'max:100',
            ],

            'e_warung' => [
                'nullable',
                'string',
                'max:200',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'between:1,100',
            ],
        ];
    }
}