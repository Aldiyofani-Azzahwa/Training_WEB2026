<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin Dinas Sosial',
                'username' => 'admin',
                'email' => 'admin@sipbpnt.test',
                'role' => 'admin_dinsos',
                'password' => Hash::make('12345'),
                'is_active' => true,
            ],
            [
                'name' => 'Manager BPNT',
                'username' => 'manager',
                'email' => 'manager@sipbpnt.test',
                'role' => 'manager',
                'password' => Hash::make('12345'),
                'is_active' => true,
            ],
            [
                'name' => 'Kepala Dinas',
                'username' => 'kepala.dinas',
                'email' => 'kepala.dinas@sipbpnt.test',
                'role' => 'kepala_dinas',
                'password' => Hash::make('12345'),
                'is_active' => true,
            ],
            [
                'name' => 'Surveyor BPNT',
                'username' => 'surveyor',
                'email' => 'surveyor@sipbpnt.test',
                'role' => 'surveyor',
                'password' => Hash::make('12345'),
                'is_active' => true,
            ],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                [
                    'username' => $user['username'],
                ],
                $user,
            );
        }
    }
}