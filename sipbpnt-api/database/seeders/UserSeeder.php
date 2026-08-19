<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class UserSeeder extends Seeder
{
    /**
     * Membuat akun awal development/testing.
     */
    public function run(): void
    {
        $this->ensureSeedingIsAllowed();

        $password = $this->initialPassword();

        $users = [
            [
                'name' => 'Admin Dinas Sosial',
                'username' => 'admin',
                'email' => 'admin@sipbpnt.test',
                'role' => UserRole::ADMIN_DINSOS,
            ],
            [
                'name' => 'Manager BPNT',
                'username' => 'manager',
                'email' => 'manager@sipbpnt.test',
                'role' => UserRole::MANAGER,
            ],
            [
                'name' => 'Kepala Dinas',
                'username' => 'kepala.dinas',
                'email' => 'kepala.dinas@sipbpnt.test',
                'role' => UserRole::KEPALA_DINAS,
            ],
            [
                'name' => 'Surveyor BPNT',
                'username' => 'surveyor',
                'email' => 'surveyor@sipbpnt.test',
                'role' => UserRole::SURVEYOR,
            ],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                [
                    'username' => $user['username'],
                ],
                [
                    ...$user,
                    'password' => $password,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * Mencegah seeder development berjalan
     * di production tanpa izin eksplisit.
     */
    private function ensureSeedingIsAllowed(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        if (
            config(
                'sipbpnt.allow_initial_user_seeding',
                false
            ) === true
        ) {
            return;
        }

        throw new RuntimeException(
            'UserSeeder diblokir di production. '.
            'Set SIPBPNT_ALLOW_INITIAL_USER_SEEDING=true '.
            'hanya jika benar-benar diperlukan.'
        );
    }

    /**
     * Mengambil password seed dari konfigurasi.
     */
    private function initialPassword(): string
    {
        $password = (string) config(
            'sipbpnt.initial_user_password',
            ''
        );

        if (mb_strlen($password) < 12) {
            throw new RuntimeException(
                'INITIAL_USER_PASSWORD wajib diisi '.
                'dengan minimal 12 karakter sebelum '.
                'menjalankan UserSeeder.'
            );
        }

        return $password;
    }
}