<?php

declare(strict_types=1);

namespace App\Http\Requests\Surveyor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreSurveyorRequest
    extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $username = trim(
            (string) $this->input(
                'username',
                ''
            )
        );

        $email =
            $this->input(
                'email'
            );

        $phone =
            $this->input(
                'phone'
            );

        $this->merge([
            'name'
                => trim(
                    (string) $this->input(
                        'name',
                        ''
                    )
                ),

            'username'
                => Str::lower(
                    $username
                ),

            'email'
                => $email === null
                    || trim(
                        (string) $email
                    ) === ''
                        ? null
                        : Str::lower(
                            trim(
                                (string) $email
                            )
                        ),

            'phone'
                => $phone === null
                    || trim(
                        (string) $phone
                    ) === ''
                        ? null
                        : trim(
                            (string) $phone
                        ),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'username' => [
                'required',
                'string',
                'max:60',
                'regex:/^[A-Za-z0-9._-]+$/',
                'unique:users,username',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
                'unique:users,email',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9+()\-\s]+$/',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'max:255',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'
                => 'Nama Surveyor wajib diisi.',

            'name.max'
                => 'Nama Surveyor maksimal 150 karakter.',

            'username.required'
                => 'Username wajib diisi.',

            'username.max'
                => 'Username maksimal 60 karakter.',

            'username.regex'
                => 'Username hanya boleh berisi huruf, angka, titik, garis bawah, dan tanda hubung.',

            'username.unique'
                => 'Username sudah digunakan.',

            'email.email'
                => 'Format email tidak valid.',

            'email.unique'
                => 'Email sudah digunakan akun lain.',

            'phone.max'
                => 'Nomor HP maksimal 20 karakter.',

            'phone.regex'
                => 'Format nomor HP tidak valid.',

            'password.required'
                => 'Kata sandi awal wajib diisi.',

            'password.min'
                => 'Kata sandi minimal 8 karakter.',

            'password.confirmed'
                => 'Konfirmasi kata sandi tidak sama.',
        ];
    }
}