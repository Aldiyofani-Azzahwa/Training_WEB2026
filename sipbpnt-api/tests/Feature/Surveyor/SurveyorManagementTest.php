<?php

declare(strict_types=1);

namespace Tests\Feature\Surveyor;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SurveyorManagementTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_only_surveyor_accounts(): void
    {
        $admin =
            $this->user(
                UserRole::ADMIN_DINSOS
            );

        User::factory()
            ->create([
                'name'
                    => 'Surveyor Aktif',

                'role'
                    => UserRole::SURVEYOR,

                'is_active'
                    => true,
            ]);

        User::factory()
            ->create([
                'name'
                    => 'Manager Tidak Masuk',

                'role'
                    => UserRole::MANAGER,

                'is_active'
                    => true,
            ]);

        $response =
            $this
                ->actingAs(
                    $admin
                )
                ->getJson(
                    '/api/v1/admin/surveyors'
                );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.name',
                'Surveyor Aktif'
            )
            ->assertJsonPath(
                'meta.total',
                1
            )
            ->assertJsonPath(
                'meta.active',
                1
            )
            ->assertJsonPath(
                'meta.inactive',
                0
            );
    }

    public function test_admin_can_create_surveyor(): void
    {
        $admin =
            $this->user(
                UserRole::ADMIN_DINSOS
            );

        $response =
            $this
                ->actingAs(
                    $admin
                )
                ->postJson(
                    '/api/v1/admin/surveyors',
                    [
                        'name'
                            => 'Surveyor Jagalan',

                        'username'
                            => 'SURVEYOR.JAGALAN',

                        'email'
                            => 'JAGALAN@EXAMPLE.TEST',

                        'phone'
                            => '081234567890',

                        'password'
                            => 'PasswordAwal123!',

                        'password_confirmation'
                            => 'PasswordAwal123!',
                    ]
                );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.username',
                'surveyor.jagalan'
            )
            ->assertJsonPath(
                'data.is_active',
                true
            );

        $surveyor =
            User::query()
                ->where(
                    'username',
                    'surveyor.jagalan'
                )
                ->firstOrFail();

        $this->assertSame(
            UserRole::SURVEYOR,
            $surveyor->role
        );

        $this->assertTrue(
            $surveyor->is_active
        );

        $this->assertTrue(
            Hash::check(
                'PasswordAwal123!',
                $surveyor->password
            )
        );
    }

    public function test_duplicate_username_is_rejected(): void
    {
        $admin =
            $this->user(
                UserRole::ADMIN_DINSOS
            );

        User::factory()
            ->create([
                'username'
                    => 'surveyor.satu',

                'role'
                    => UserRole::SURVEYOR,
            ]);

        $this
            ->actingAs(
                $admin
            )
            ->postJson(
                '/api/v1/admin/surveyors',
                [
                    'name'
                        => 'Surveyor Duplikat',

                    'username'
                        => 'surveyor.satu',

                    'email'
                        => null,

                    'phone'
                        => null,

                    'password'
                        => 'PasswordAwal123!',

                    'password_confirmation'
                        => 'PasswordAwal123!',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'username'
            );
    }

    public function test_admin_can_update_surveyor_without_changing_role(): void
    {
        $admin =
            $this->user(
                UserRole::ADMIN_DINSOS
            );

        $surveyor =
            $this->user(
                UserRole::SURVEYOR
            );

        $this
            ->actingAs(
                $admin
            )
            ->patchJson(
                "/api/v1/admin/surveyors/{$surveyor->id}",
                [
                    'name'
                        => 'Nama Surveyor Baru',

                    'username'
                        => 'surveyor.baru',

                    'email'
                        => 'surveyor.baru@example.test',

                    'phone'
                        => '081111111111',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.name',
                'Nama Surveyor Baru'
            );

        $surveyor->refresh();

        $this->assertSame(
            UserRole::SURVEYOR,
            $surveyor->role
        );

        $this->assertSame(
            'surveyor.baru',
            $surveyor->username
        );
    }

    public function test_admin_can_deactivate_and_reactivate_surveyor(): void
    {
        $admin =
            $this->user(
                UserRole::ADMIN_DINSOS
            );

        $surveyor =
            $this->user(
                UserRole::SURVEYOR
            );

        $this
            ->actingAs(
                $admin
            )
            ->patchJson(
                "/api/v1/admin/surveyors/{$surveyor->id}/status",
                [
                    'is_active'
                        => false,
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.is_active',
                false
            );

        $this->assertFalse(
            $surveyor
                ->fresh()
                ->is_active
        );

        $this
            ->actingAs(
                $admin
            )
            ->patchJson(
                "/api/v1/admin/surveyors/{$surveyor->id}/status",
                [
                    'is_active'
                        => true,
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.is_active',
                true
            );

        $this->assertTrue(
            $surveyor
                ->fresh()
                ->is_active
        );
    }

    public function test_manager_cannot_manage_surveyor_accounts(): void
    {
        $manager =
            $this->user(
                UserRole::MANAGER
            );

        $this
            ->actingAs(
                $manager
            )
            ->getJson(
                '/api/v1/admin/surveyors'
            )
            ->assertForbidden();
    }

    public function test_manager_options_only_contains_active_surveyors(): void
    {
        $manager =
            $this->user(
                UserRole::MANAGER
            );

        $active =
            User::factory()
                ->create([
                    'name'
                        => 'Surveyor Aktif',

                    'role'
                        => UserRole::SURVEYOR,

                    'is_active'
                        => true,
                ]);

        User::factory()
            ->create([
                'name'
                    => 'Surveyor Nonaktif',

                'role'
                    => UserRole::SURVEYOR,

                'is_active'
                    => false,
            ]);

        $response =
            $this
                ->actingAs(
                    $manager
                )
                ->getJson(
                    '/api/v1/surveyors/options'
                );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $active->id
            )
            ->assertJsonPath(
                'data.0.name',
                'Surveyor Aktif'
            );
    }

    public function test_there_is_no_delete_endpoint_for_surveyor(): void
    {
        $admin =
            $this->user(
                UserRole::ADMIN_DINSOS
            );

        $surveyor =
            $this->user(
                UserRole::SURVEYOR
            );

        $this
            ->actingAs(
                $admin
            )
            ->deleteJson(
                "/api/v1/admin/surveyors/{$surveyor->id}"
            )
            ->assertStatus(
                405
            );

        $this->assertDatabaseHas(
            'users',
            [
                'id'
                    => $surveyor->id,

                'role'
                    => UserRole::SURVEYOR
                        ->value,
            ]
        );
    }

    private function user(
        UserRole $role
    ): User {
        return User::factory()
            ->create([
                'role'
                    => $role,

                'is_active'
                    => true,
            ]);
    }
}