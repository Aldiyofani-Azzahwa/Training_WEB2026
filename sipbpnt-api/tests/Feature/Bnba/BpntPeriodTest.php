<?php

declare(strict_types=1);

namespace Tests\Feature\Bnba;

use App\Enums\BnbaImportStatus;
use App\Enums\UserRole;
use App\Models\BnbaImport;
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
            $this->createAdmin();

        BpntPeriod::query()
            ->create([
                'code'
                    => 'BPNT-2026-TEST',

                'name'
                    => 'BPNT Januari',

                'year'
                    => 2026,
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
                'BPNT-2026-TEST'
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

        $periodData =
            $response->json(
                'data.0'
            );

        $this->assertIsArray(
            $periodData
        );

        $this->assertArrayNotHasKey(
            'is_active',
            $periodData
        );
    }

    public function test_admin_can_create_bpnt_period(): void
    {
        $admin =
            $this->createAdmin();

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

        $periodData =
            $response->json(
                'data'
            );

        $this->assertIsArray(
            $periodData
        );

        $this->assertArrayNotHasKey(
            'is_active',
            $periodData
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
            ]
        );
    }

    public function test_admin_can_update_name_and_year_when_period_has_no_bnba(): void
    {
        $admin =
            $this->createAdmin();

        $period =
            $this->createPeriod();

        $response =
            $this
                ->actingAs($admin)
                ->patchJson(
                    "/api/v1/bpnt-periods/{$period->id}",
                    [
                        'name'
                            => 'BPNT September',

                        'year'
                            => 2027,
                    ]
                );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Periode BPNT berhasil diperbarui.'
            )
            ->assertJsonPath(
                'data.name',
                'BPNT September'
            )
            ->assertJsonPath(
                'data.year',
                2027
            )
            ->assertJsonPath(
                'data.can_edit_year',
                true
            )
            ->assertJsonPath(
                'data.can_delete',
                true
            );

        $this->assertDatabaseHas(
            'bpnt_periods',
            [
                'id'
                    => $period->id,

                'name'
                    => 'BPNT September',

                'year'
                    => 2027,
            ]
        );
    }

    public function test_admin_can_update_name_when_period_has_bnba(): void
    {
        $admin =
            $this->createAdmin();

        $period =
            $this->createPeriod();

        $this->createBnbaImport(
            $period,
            $admin
        );

        $response =
            $this
                ->actingAs($admin)
                ->patchJson(
                    "/api/v1/bpnt-periods/{$period->id}",
                    [
                        'name'
                            => 'BPNT Agustus Revisi Nama',

                        /*
                         * Tahun tetap sama.
                         */
                        'year'
                            => 2026,
                    ]
                );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.name',
                'BPNT Agustus Revisi Nama'
            )
            ->assertJsonPath(
                'data.year',
                2026
            )
            ->assertJsonPath(
                'data.can_edit_year',
                false
            )
            ->assertJsonPath(
                'data.can_delete',
                false
            );

        $this->assertDatabaseHas(
            'bpnt_periods',
            [
                'id'
                    => $period->id,

                'name'
                    => 'BPNT Agustus Revisi Nama',

                'year'
                    => 2026,
            ]
        );
    }

    public function test_admin_cannot_update_year_when_period_has_bnba(): void
    {
        $admin =
            $this->createAdmin();

        $period =
            $this->createPeriod();

        $this->createBnbaImport(
            $period,
            $admin
        );

        $response =
            $this
                ->actingAs($admin)
                ->patchJson(
                    "/api/v1/bpnt-periods/{$period->id}",
                    [
                        'name'
                            => 'BPNT Agustus',

                        'year'
                            => 2027,
                    ]
                );

        $response
            ->assertStatus(422)
            ->assertJsonPath(
                'errors.year.0',
                'Tahun tidak dapat diubah selama periode masih memiliki data BNBA. Hapus BNBA terlebih dahulu.'
            );

        $this->assertDatabaseHas(
            'bpnt_periods',
            [
                'id'
                    => $period->id,

                'year'
                    => 2026,
            ]
        );
    }

    public function test_admin_can_delete_period_without_bnba(): void
    {
        $admin =
            $this->createAdmin();

        $period =
            $this->createPeriod();

        $this
            ->actingAs($admin)
            ->deleteJson(
                "/api/v1/bpnt-periods/{$period->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Periode BPNT berhasil dihapus.'
            );

        $this->assertDatabaseMissing(
            'bpnt_periods',
            [
                'id'
                    => $period->id,
            ]
        );
    }

    public function test_admin_cannot_delete_period_that_has_bnba(): void
    {
        $admin =
            $this->createAdmin();

        $period =
            $this->createPeriod();

        $this->createBnbaImport(
            $period,
            $admin
        );

        $this
            ->actingAs($admin)
            ->deleteJson(
                "/api/v1/bpnt-periods/{$period->id}"
            )
            ->assertStatus(422)
            ->assertJsonPath(
                'errors.period.0',
                'Periode masih memiliki data BNBA. Hapus BNBA terlebih dahulu sebelum menghapus periode.'
            );

        $this->assertDatabaseHas(
            'bpnt_periods',
            [
                'id'
                    => $period->id,
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

                    /*
                     * Ini status akun,
                     * bukan status periode.
                     */
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

    private function createAdmin(): User
    {
        return User::factory()
            ->create([
                'role'
                    => UserRole::ADMIN_DINSOS,

                /*
                 * Status akun user.
                 * Harus tetap ada.
                 */
                'is_active'
                    => true,
            ]);
    }

    private function createPeriod(): BpntPeriod
    {
        return BpntPeriod::query()
            ->create([
                'code'
                    => 'BPNT-2026-TEST',

                'name'
                    => 'BPNT Agustus',

                'year'
                    => 2026,
            ]);
    }

    private function createBnbaImport(
        BpntPeriod $period,
        User $admin
    ): BnbaImport {
        return BnbaImport::query()
            ->create([
                'bpnt_period_id'
                    => $period->id,

                'uploaded_by'
                    => $admin->id,

                'status'
                    => BnbaImportStatus
                        ::PREVIEW_READY,

                'original_name'
                    => 'bnba-test.xlsx',

                'stored_path'
                    => 'bnba-imports/test/bnba-test.xlsx',

                'file_sha256'
                    => str_repeat(
                        'a',
                        64
                    ),
            ]);
    }
}