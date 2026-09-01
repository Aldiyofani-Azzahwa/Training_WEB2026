<?php

declare(strict_types=1);

namespace App\Http\Requests\Surveyor;

use App\Enums\KpmVerificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKpmVerificationRequest
    extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => $this->filled('reason')
                ? trim((string) $this->input('reason'))
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
            'bpnt_participant_id' => [
                'required',
                'integer',
                'min:1',
            ],

            'status' => [
                'required',
                Rule::enum(KpmVerificationStatus::class),
            ],

            'reason' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->input('status')
                        === KpmVerificationStatus::NOT_CLAIMED->value
                ),

                Rule::prohibitedIf(
                    fn (): bool =>
                        in_array(
                            $this->input('status'),
                            [
                                KpmVerificationStatus::DECEASED->value,
                                KpmVerificationStatus::MOVED_DOMICILE->value,
                            ],
                            true
                        )
                ),

                'nullable',
                'string',
                'max:1000',
            ],

            'period_id' => ['prohibited'],
            'kelurahan_id' => ['prohibited'],
            'surveyor_id' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'bpnt_participant_id.required'
                => 'KPM wajib dipilih.',

            'status.required'
                => 'Status verifikasi wajib dipilih.',

            'reason.required'
                => 'Alasan wajib diisi untuk status Tidak Transaksi.',

            'reason.prohibited'
                => 'Alasan hanya boleh diisi untuk status Tidak Transaksi.',

            '*.prohibited'
                => 'Field :attribute tidak boleh dikirim.',
        ];
    }
}