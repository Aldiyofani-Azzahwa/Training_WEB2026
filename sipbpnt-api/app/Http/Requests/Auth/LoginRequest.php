<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Login dapat digunakan oleh pengguna yang belum autentikasi.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi form login.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'max:60',
            ],

            'password' => [
                'required',
                'string',
                'max:255',
            ],

            'remember' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Pesan validasi berbahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'Username wajib diisi.',
            'username.max' => 'Username maksimal 60 karakter.',
            'password.required' => 'Kata sandi wajib diisi.',
        ];
    }

    /**
     * Melakukan autentikasi pengguna.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $authenticated = Auth::attempt([
            'username' => $this->string('username')->toString(),
            'password' => $this->string('password')->toString(),
            'is_active' => true,
        ], $this->boolean('remember'));

        if (! $authenticated) {
            RateLimiter::hit(
                $this->throttleKey(),
                60
            );

            throw ValidationException::withMessages([
                'username' => [
                    'Username atau kata sandi tidak benar, atau akun tidak aktif.',
                ],
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Memastikan login tidak melebihi batas percobaan.
     *
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts(
            $this->throttleKey(),
            5
        )) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn(
            $this->throttleKey()
        );

        throw ValidationException::withMessages([
            'username' => [
                "Terlalu banyak percobaan login. Coba kembali dalam {$seconds} detik.",
            ],
        ]);
    }

    /**
     * Membuat kunci pembatasan berdasarkan username dan IP.
     */
    private function throttleKey(): string
    {
        $username = Str::lower(
            $this->string('username')->toString()
        );

        return Str::transliterate(
            $username.'|'.$this->ip()
        );
    }
}