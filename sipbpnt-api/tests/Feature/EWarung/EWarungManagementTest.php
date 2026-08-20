<?php

declare(strict_types=1);

namespace Tests\Feature\EWarung;

use App\Enums\UserRole;
use App\Models\EWarung;
use App\Models\User;
use Database\Seeders\EWarungSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EWarungManagementTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_exactly_thirteen_initial_e_warungs(): void
    {
        $this->seed(
            EWarungSeeder::class
        );

        $this->assertDatabaseCount(
            'e_warungs',
            13
        );

        $this->assertDatabaseHas(
            'e_warungs',
            [
                'name'
                    => 'E-WAROENG ANGGREK SURODINAWAN',

                'is_active'
                    => true,
            ]
        );

        $this->assertDatabaseHas(
            'e_warungs',
            [
                'name'
                    => 'E-WAROENG TERATAI PULOREJO',

                'is_active'
                    => true,
            ]
        );
    }

    public function test_seeder_is_idempotent_and_does_not_reactivate_existing_e_warung(): void
    {
        $this->seed(
            EWarungSeeder::class
        );

        $eWarung =
            EWarung::query()
                ->where(
                    'name',
                    'E-WAROENG I MERI'
                )
                ->firstOrFail();

        $eWarung->update([
            'is_active' => false,
        ]);

        $this->seed(
            EWarungSeeder::class
        );

        $this->assertDatabaseCount(
            'e_warungs',
            13
        );

        $this->assertDatabaseHas(
            'e_warungs',
            [
                'id'
                    => $eWarung->id,

                'is_active'
                    => false,
            ]
        );
    }

    public function test_admin_can_list_e_warungs(): void
    {
        $admin =
            $this->user(
                UserRole::ADMIN_DINSOS
            );

        $this->seed(
            EWarungSeeder::class
        );

        $response =
            $this
                ->actingAs(
                    $admin
                )
                ->getJson(
                    '/api/v1/admin/e-warungs'
                );

        $response
            ->assertOk()
            ->assertJsonCount(
                13,
                'data'
            )
            ->assertJsonPath(
                'meta.total',
                13
            )
            ->assertJsonPath(
                'meta.active',
                13
            )
            ->assertJsonPath(
                'meta.inactive',
                0
            );
    }

    public function test_admin_can_create_e_warung(): void
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
                    '/api/v1/admin/e-warungs',
                    [
                        'name'
                            => '  E-WAROENG BARU  ',
                    ]
                );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.name',
                'E-WAROENG BARU'
            )
            ->assertJsonPath(
                'data.is_active',
                true
            );

        $this->assertDatabaseHas(
            'e_warungs',
            [
                'name'
                    => 'E-WAROENG BARU',

                'is_active'
                    => true,
            ]
        );
    }

    public function test_duplicate_name_is_rejected(): void
    {
        $admin =
            $this->user(
                UserRole::ADMIN_DINSOS
            );

        EWarung::query()
            ->create([
                'name'
                    => 'E-WAROENG DUPLIKAT',

                'is_active'
                    => true,
            ]);

        $this
            ->actingAs(
                $admin
            )
            ->postJson(
                '/api/v1/admin/e-warungs',
                [
                    'name'
                        => 'E-WAROENG DUPLIKAT',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'name'
            );
    }

    public function test_admin_can_update_e_warung(): void
    {
        $admin =
            $this->user(
                UserRole::ADMIN_DINSOS
            );

        $eWarung =
            EWarung::query()
                ->create([
                    'name'
                        => 'E-WAROENG LAMA',

                    'is_active'
                        => true,
                ]);

        $this
            ->actingAs(
                $admin
            )
            ->patchJson(
                "/api/v1/admin/e-warungs/{$eWarung->id}",
                [
                    'name'
                        => 'E-WAROENG BARU',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.name',
                'E-WAROENG BARU'
            );

        $this->assertDatabaseHas(
            'e_warungs',
            [
                'id'
                    => $eWarung->id,

                'name'
                    => 'E-WAROENG BARU',
            ]
        );
    }

    public function test_admin_can_deactivate_and_reactivate_e_warung(): void
    {
        $admin =
            $this->user(
                UserRole::ADMIN_DINSOS
            );

        $eWarung =
            EWarung::query()
                ->create([
                    'name'
                        => 'E-WAROENG STATUS',

                    'is_active'
                        => true,
                ]);

        $this
            ->actingAs(
                $admin
            )
            ->patchJson(
                "/api/v1/admin/e-warungs/{$eWarung->id}/status",
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

        $this
            ->actingAs(
                $admin
            )
            ->patchJson(
                "/api/v1/admin/e-warungs/{$eWarung->id}/status",
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
    }

    public function test_admin_can_delete_unused_e_warung(): void
    {
        $admin =
            $this->user(
                UserRole::ADMIN_DINSOS
            );

        $eWarung =
            EWarung::query()
                ->create([
                    'name'
                        => 'E-WAROENG BELUM DIPAKAI',

                    'is_active'
                        => true,
                ]);

        $this
            ->actingAs(
                $admin
            )
            ->deleteJson(
                "/api/v1/admin/e-warungs/{$eWarung->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'E-Warung berhasil dihapus.'
            );

        $this->assertDatabaseMissing(
            'e_warungs',
            [
                'id'
                    => $eWarung->id,
            ]
        );
    }

    public function test_used_e_warung_cannot_be_deleted(): void
    {
        $admin =
            $this->user(
                UserRole::ADMIN_DINSOS
            );

        $eWarung =
            EWarung::query()
                ->create([
                    'name'
                        => 'E-WAROENG SUDAH DIPAKAI',

                    'is_active'
                        => true,
                ]);

        Schema::create(
            'transactions',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->unsignedBigInteger(
                        'e_warung_id'
                    );
            }
        );

        DB::table(
            'transactions'
        )->insert([
            'e_warung_id'
                => $eWarung->id,
        ]);

        $this
            ->actingAs(
                $admin
            )
            ->deleteJson(
                "/api/v1/admin/e-warungs/{$eWarung->id}"
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'e_warung'
            );

        $this->assertDatabaseHas(
            'e_warungs',
            [
                'id'
                    => $eWarung->id,
            ]
        );
    }

    public function test_manager_cannot_manage_e_warungs(): void
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
                '/api/v1/admin/e-warungs'
            )
            ->assertForbidden();
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