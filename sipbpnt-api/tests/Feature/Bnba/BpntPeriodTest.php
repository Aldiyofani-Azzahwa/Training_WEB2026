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
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN_DINSOS,
            'is_active' => true,
        ]);

        BpntPeriod::query()->create([
            'code' => 'BPNT-2026-01',
            'name' => 'BPNT Tahun 2026',
            'year' => 2026,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->getJson('/api/v1/bpnt-periods');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.code',
                'BPNT-2026-01'
            )
            ->assertJsonPath(
                'data.0.year',
                2026
            )
            ->assertJsonPath(
                'data.0.is_active',
                true
            );
    }

    public function test_admin_can_create_bpnt_period(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN_DINSOS,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->postJson(
                '/api/v1/bpnt-periods',
                [
                    'code' => 'BPNT-2026-01',
                    'name' => 'BPNT Tahun 2026',
                    'year' => 2026,
                    'is_active' => true,
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Periode BPNT berhasil dibuat.'
            )
            ->assertJsonPath(
                'data.code',
                'BPNT-2026-01'
            );

        $this->assertDatabaseHas(
            'bpnt_periods',
            [
                'code' => 'BPNT-2026-01',
                'year' => 2026,
                'is_active' => true,
            ]
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'user_id' => $admin->id,
                'action' => 'bpnt.period.created',
            ]
        );
    }

    public function test_surveyor_cannot_create_bpnt_period(): void
    {
        $surveyor = User::factory()->create([
            'role' => UserRole::SURVEYOR,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($surveyor)
            ->postJson(
                '/api/v1/bpnt-periods',
                [
                    'code' => 'BPNT-2026-01',
                    'name' => 'BPNT Tahun 2026',
                    'year' => 2026,
                    'is_active' => true,
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseMissing(
            'bpnt_periods',
            [
                'code' => 'BPNT-2026-01',
            ]
        );
    }
}