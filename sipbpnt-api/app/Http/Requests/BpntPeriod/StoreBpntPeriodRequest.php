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
            'name' => [
                'required',
                'string',
                'max:150',
                \Illuminate\Validation\Rule::unique('bpnt_periods', 'name')->where(function ($query) {
                    return $query->where('year', $this->year);
                }),
            ],

            'year' => [
                'required',
                'integer',
                'between:2000,2100',
            ],
        ];
    }
}