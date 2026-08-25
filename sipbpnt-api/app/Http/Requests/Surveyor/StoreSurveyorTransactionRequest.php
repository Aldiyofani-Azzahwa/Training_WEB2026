<?php

declare(strict_types=1);

namespace App\Http\Requests\Surveyor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSurveyorTransactionRequest
    extends FormRequest
{
    protected function prepareForValidation(): void
    {
        /*
         * Jangan membuat key NIK jika request
         * memakai bpnt_participant_id.
         */
        if (! $this->has('nik')) {
            return;
        }

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
            /*
             * Jalur Scan KTP / exact NIK.
             */
            'nik' => [
                'bail',
                'nullable',
                'required_without:bpnt_participant_id',
                'string',
                'regex:/^\d{16}$/',
            ],

            /*
             * Jalur langsung dari halaman KPM.
             */
            'bpnt_participant_id' => [
                'bail',
                'nullable',
                'required_without:nik',
                'integer',
                'min:1',
            ],

            'e_warung_id' => [
                'required',
                'integer',
                'min:1',
            ],

            'period_id' => [
                'prohibited',
            ],

            'participant_id' => [
                'prohibited',
            ],

            'kelurahan_id' => [
                'prohibited',
            ],

            'surveyor_id' => [
                'prohibited',
            ],

            'transacted_at' => [
                'prohibited',
            ],

            'nominal' => [
                'prohibited',
            ],

            'amount' => [
                'prohibited',
            ],

            'saldo' => [
                'prohibited',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nik.required'
                => 'NIK wajib diisi.',

            'nik.required_without'
                => 'NIK atau KPM wajib dipilih.',

            'nik.regex'
                => 'NIK harus terdiri dari 16 digit angka.',

            'e_warung_id.required'
                => 'E-Warung wajib dipilih.',

            'e_warung_id.integer'
                => 'E-Warung tidak valid.',

            'bpnt_participant_id.required_without'
                => 'NIK atau KPM wajib dipilih.',

            'bpnt_participant_id.integer'
                => 'KPM tidak valid.',

            '*.prohibited'
                => 'Field :attribute tidak boleh dikirim.',
        ];
    }

    public function withValidator(
        Validator $validator
    ): void {
        /*
         * NIK dan participant ID adalah dua
         * entry point berbeda. Keduanya tidak
         * boleh dikirim bersamaan.
         */
        $validator->after(
            function (
                Validator $validator
            ): void {
                if (
                    ! $this->filled(
                        'nik'
                    )
                    ||
                    ! $this->filled(
                        'bpnt_participant_id'
                    )
                ) {
                    return;
                }

                $validator
                    ->errors()
                    ->add(
                        'nik',
                        'NIK tidak boleh dikirim bersama ID KPM.'
                    );

                $validator
                    ->errors()
                    ->add(
                        'bpnt_participant_id',
                        'ID KPM tidak boleh dikirim bersama NIK.'
                    );
            }
        );
    }
}