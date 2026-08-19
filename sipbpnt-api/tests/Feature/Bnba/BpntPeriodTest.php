<?php

declare(strict_types=1);

namespace Tests\Feature\Bnba;

use App\Enums\UserRole;
use App\Models\BpntPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BpntPeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_get_bpnt_periods(): void
    {
        $admin =
            User::factory()
                ->create([
                    'role'
                        => UserRole::ADMIN_DINSOS,

                    'is_active'
                        => true,
                ]);

        BpntPeriod::query()
            ->create([
                'code'
                    => 'BPNT-2026-01',

                'name'
                    => 'BPNT Januari',

                'year'
                    => 2026,

                /*
                 * Legacy field.
                 * Tidak lagi menentukan
                 * business flow.
                 */
                'is_active'
                    => false,
            ]);

        $response =
            $this
                ->actingAs($admin)
                ->getJson(
                    '/api/v1/bpnt-periods'
                );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.code',
                'BPNT-2026-01'
            )
            ->assertJsonPath(
                'data.0.name',
                'BPNT Januari'
            )
            ->assertJsonPath(
                'data.0.year',
                2026
            )
            ->assertJsonPath(
                'data.0.imports_count',
                0
            )
            ->assertJsonPath(
                'data.0.participants_count',
                0
            )
            ->assertJsonPath(
                'data.0.can_delete',
                true
            )
            ->assertJsonPath(
                'data.0.can_edit_year',
                true
            )
            ->assertJsonPath(
                'data.0.bnba',
                null
            );
    }

    public function test_admin_can_create_bpnt_period(): void
    {
        $admin =
            User::factory()
                ->create([
                    'role'
                        => UserRole::ADMIN_DINSOS,

                    'is_active'
                        => true,
                ]);

        /*
         * Admin hanya mengirim:
         *
         * name
         * year
         *
         * code tidak diinput Admin.
         */
        $response =
            $this
                ->actingAs($admin)
                ->postJson(
                    '/api/v1/bpnt-periods',
                    [
                        'name'
                            => 'BPNT Agustus',

                        'year'
                            => 2026,
                    ]
                );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Periode BPNT berhasil dibuat.'
            )
            ->assertJsonPath(
                'data.name',
                'BPNT Agustus'
            )
            ->assertJsonPath(
                'data.year',
                2026
            )
            ->assertJsonPath(
                'data.is_active',
                false
            )
            ->assertJsonPath(
                'data.imports_count',
                0
            )
            ->assertJsonPath(
                'data.participants_count',
                0
            )
            ->assertJsonPath(
                'data.can_delete',
                true
            )
            ->assertJsonPath(
                'data.can_edit_year',
                true
            )
            ->assertJsonPath(
                'data.bnba',
                null
            );

        $code =
            $response->json(
                'data.code'
            );

        $this->assertIsString(
            $code
        );

        $this->assertStringStartsWith(
            'BPNT-2026-',
            $code
        );

        $this->assertDatabaseHas(
            'bpnt_periods',
            [
                'name'
                    => 'BPNT Agustus',

                'year'
                    => 2026,

                'is_active'
                    => false,
            ]
        );
    }

    public function test_surveyor_cannot_create_bpnt_period(): void
    {
        $surveyor =
            User::factory()
                ->create([
                    'role'
                        => UserRole::SURVEYOR,

                    'is_active'
                        => true,
                ]);

        $this
            ->actingAs($surveyor)
            ->postJson(
                '/api/v1/bpnt-periods',
                [
                    'name'
                        => 'BPNT Agustus',

                    'year'
                        => 2026,
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseCount(
            'bpnt_periods',
            0
        );
    }
}