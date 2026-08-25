<?php

declare(strict_types=1);

namespace App\Http\Requests\Assignment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSurveyorAssignmentRequest
    extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kelurahan_id' => [
                'required',
                'integer',
                'exists:kelurahans,id',
            ],

            'surveyor_id' => [
                'required',
                'integer',

                Rule::exists(
                    'users',
                    'id'
                )->where(
                    'role',
                    'surveyor'
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'kelurahan_id.required'
                => 'Kelurahan wajib dipilih.',

            'kelurahan_id.exists'
                => 'Kelurahan tidak ditemukan.',

            'surveyor_id.required'
                => 'Surveyor wajib dipilih.',

            'surveyor_id.exists'
                => 'Surveyor tidak ditemukan.',
        ];
    }
}