<?php

declare(strict_types=1);

namespace App\Http\Requests\Assignment;

use Illuminate\Foundation\Http\FormRequest;

class ListSurveyorAssignmentRequest
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
        ];
    }
}