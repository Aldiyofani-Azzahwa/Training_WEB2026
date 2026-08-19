<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_seeder_creates_four_initial_users_with_configured_password():
        void
    {
        config()->set(
            'sipbpnt.initial_user_password',
            'LocalOnlyPassword123!'
        );

        $this->seed(
            UserSeeder::class
        );

        $this->assertDatabaseCount(
            'users',
            4
        );

        $expectedUsers = [
            'admin' =>
                UserRole::ADMIN_DINSOS,

            'manager' =>
                UserRole::MANAGER,

            'surveyor' =>
                UserRole::SURVEYOR,

            'kepala.dinas' =>
                UserRole::KEPALA_DINAS,
        ];

        foreach (
            $expectedUsers as
            $username => $role
        ) {
            $this->assertDatabaseHas(
                'users',
                [
                    'username' =>
                        $username,

                    'role' =>
                        $role->value,

                    'is_active' =>
                        true,
                ]
            );
        }

        $admin = User::query()
            ->where(
                'username',
                'admin'
            )
            ->firstOrFail();

        $this->assertTrue(
            Hash::check(
                'LocalOnlyPassword123!',
                $admin->password
            )
        );
    }

    public function test_user_seeder_rejects_weak_password():
        void
    {
        config()->set(
            'sipbpnt.initial_user_password',
            '12345'
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'INITIAL_USER_PASSWORD wajib diisi '.
            'dengan minimal 12 karakter'
        );

        $this->seed(
            UserSeeder::class
        );
    }
}