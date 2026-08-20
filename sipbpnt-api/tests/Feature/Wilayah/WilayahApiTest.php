<?php

declare(strict_types=1);

namespace Tests\Feature\Wilayah;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WilayahApiTest
    extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            WilayahSeeder::class
        );
    }

    public function test_admin_can_read_master_wilayah(): void
    {
        $admin =
            User::factory()
                ->create([
                    'role'
                        => UserRole
                            ::ADMIN_DINSOS,

                    'is_active'
                        => true,
                ]);

        $response =
            $this
                ->actingAs(
                    $admin
                )
                ->getJson(
                    '/api/v1/wilayah'
                );

        $response
            ->assertOk()
            ->assertJsonCount(
                3,
                'data'
            )
            ->assertJsonPath(
                'meta.kecamatans_count',
                3
            )
            ->assertJsonPath(
                'meta.kelurahans_count',
                18
            )
            ->assertJsonFragment([
                'name'
                    => 'Jagalan',

                'code'
                    => '1004',
            ]);
    }

    public function test_manager_can_read_master_wilayah_for_assignment(): void
    {
        $manager =
            User::factory()
                ->create([
                    'role'
                        => UserRole
                            ::MANAGER,

                    'is_active'
                        => true,
                ]);

        $this
            ->actingAs(
                $manager
            )
            ->getJson(
                '/api/v1/wilayah'
            )
            ->assertOk()
            ->assertJsonCount(
                3,
                'data'
            );
    }

    public function test_surveyor_cannot_read_master_wilayah_endpoint(): void
    {
        $surveyor =
            User::factory()
                ->create([
                    'role'
                        => UserRole
                            ::SURVEYOR,

                    'is_active'
                        => true,
                ]);

        $this
            ->actingAs(
                $surveyor
            )
            ->getJson(
                '/api/v1/wilayah'
            )
            ->assertForbidden();
    }
}