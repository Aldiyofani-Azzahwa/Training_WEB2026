<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Password default untuk pengujian.
     */
    protected static ?string $password = null;

    /**
     * Data default pengguna.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),

            'username' => fake()
                ->unique()
                ->userName(),

            'email' => fake()
                ->unique()
                ->safeEmail(),

            'phone' => fake()->optional()->numerify(
                '08##########'
            ),

            'email_verified_at' => now(),

            'password' => static::$password ??= Hash::make(
                '12345'
            ),

            'role' => UserRole::SURVEYOR,

            'is_active' => true,

            'remember_token' => Str::random(10),

            'last_login_at' => null,
        ];
    }

    /**
     * Membuat email pengguna belum diverifikasi.
     */
    public function unverified(): static
    {
        return $this->state(fn (): array => [
            'email_verified_at' => null,
        ]);
    }
}