<?php

declare(strict_types=1);

namespace Tests\Feature\EWarung;

use App\Enums\UserRole;
use App\Models\BpntPeriod;
use App\Models\EWarung;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\User;
use App\Support\Security\SensitiveIdentity;
use Database\Seeders\EWarungSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EWarungManagementTest extends TestCase
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
                'name' => 'E-WAROENG ANGGREK SURODINAWAN',
                'is_active' => true,
            ]
        );

        $this->assertDatabaseHas(
            'e_warungs',
            [
                'name' => 'E-WAROENG TERATAI PULOREJO',
                'is_active' => true,
            ]
        );
    }

    public function test_seeder_is_idempotent_and_does_not_reactivate_existing_e_warung(): void
    {
        $this->seed(
            EWarungSeeder::class
        );

        $eWarung = EWarung::query()
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
                'id' => $eWarung->id,
                'is_active' => false,
            ]
        );
    }

    public function test_admin_can_list_e_warungs(): void
    {
        $admin = $this->user(
            UserRole::ADMIN_DINSOS
        );

        $this->seed(
            EWarungSeeder::class
        );

        $this->actingAs($admin)
            ->getJson(
                '/api/v1/admin/e-warungs'
            )
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
        $admin = $this->user(
            UserRole::ADMIN_DINSOS
        );

        $this->actingAs($admin)
            ->postJson(
                '/api/v1/admin/e-warungs',
                [
                    'name' => '  E-WAROENG BARU  ',
                ]
            )
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
                'name' => 'E-WAROENG BARU',
                'is_active' => true,
            ]
        );
    }

    public function test_duplicate_name_is_rejected(): void
    {
        $admin = $this->user(
            UserRole::ADMIN_DINSOS
        );

        EWarung::query()->create([
            'name' => 'E-WAROENG DUPLIKAT',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->postJson(
                '/api/v1/admin/e-warungs',
                [
                    'name' => 'E-WAROENG DUPLIKAT',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'name'
            );
    }

    public function test_admin_can_update_e_warung(): void
    {
        $admin = $this->user(
            UserRole::ADMIN_DINSOS
        );

        $eWarung = EWarung::query()->create([
            'name' => 'E-WAROENG LAMA',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patchJson(
                "/api/v1/admin/e-warungs/{$eWarung->id}",
                [
                    'name' => 'E-WAROENG BARU',
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
                'id' => $eWarung->id,
                'name' => 'E-WAROENG BARU',
            ]
        );
    }

    public function test_admin_can_deactivate_and_reactivate_e_warung(): void
    {
        $admin = $this->user(
            UserRole::ADMIN_DINSOS
        );

        $eWarung = EWarung::query()->create([
            'name' => 'E-WAROENG STATUS',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patchJson(
                "/api/v1/admin/e-warungs/{$eWarung->id}/status",
                [
                    'is_active' => false,
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.is_active',
                false
            );

        $this->actingAs($admin)
            ->patchJson(
                "/api/v1/admin/e-warungs/{$eWarung->id}/status",
                [
                    'is_active' => true,
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
        $admin = $this->user(
            UserRole::ADMIN_DINSOS
        );

        $eWarung = EWarung::query()->create([
            'name' => 'E-WAROENG BELUM DIPAKAI',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
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
                'id' => $eWarung->id,
            ]
        );
    }

    public function test_used_e_warung_cannot_be_deleted(): void
    {
        $admin = $this->user(
            UserRole::ADMIN_DINSOS
        );

        $eWarung = EWarung::query()->create([
            'name' => 'E-WAROENG SUDAH DIPAKAI',
            'is_active' => true,
        ]);

        /*
         * Tabel transactions sekarang sudah dibuat
         * oleh migration transaksi sebenarnya.
         *
         * Jangan membuat tabel dummy lagi.
         */
        $this->createTransactionUsing(
            $eWarung,
            $admin
        );

        $this->actingAs($admin)
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
                'id' => $eWarung->id,
            ]
        );

        $this->assertDatabaseHas(
            'transactions',
            [
                'e_warung_id' => $eWarung->id,
            ]
        );
    }

    public function test_manager_cannot_manage_e_warungs(): void
    {
        $manager = $this->user(
            UserRole::MANAGER
        );

        $this->actingAs($manager)
            ->getJson(
                '/api/v1/admin/e-warungs'
            )
            ->assertForbidden();
    }

    private function user(
        UserRole $role
    ): User {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function createTransactionUsing(
        EWarung $eWarung,
        User $uploader
    ): void {
        $surveyor = $this->user(
            UserRole::SURVEYOR
        );

        $period = BpntPeriod::query()->create([
            'code' => 'E-WARUNG-TEST',
            'name' => 'Periode Test E-Warung',
            'year' => 2026,
            'is_active' => true,
            'active_slot' => 1,
        ]);

        $kecamatan = Kecamatan::query()->create([
            'code' => 'KEC0001',
            'name' => 'KECAMATAN TEST',
        ]);

        $kelurahan = Kelurahan::query()->create([
            'kecamatan_id' => $kecamatan->id,
            'code' => 'K001',
            'name' => 'KELURAHAN TEST',
        ]);

        $now = now();

        $importId = (int) DB::table(
            'bnba_imports'
        )->insertGetId([
            'bpnt_period_id' => $period->id,
            'uploaded_by' => $uploader->id,
            'confirmed_by' => $uploader->id,
            'status' => 'confirmed',
            'original_name' => 'e-warung-test.xlsx',
            'stored_path' => 'tests/e-warung-test.xlsx',

            'file_sha256' => hash(
                'sha256',
                'e-warung-test'
            ),

            'total_rows' => 1,
            'valid_rows' => 1,
            'warning_rows' => 0,
            'invalid_rows' => 0,
            'duplicate_rows' => 0,
            'confirmed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        /** @var SensitiveIdentity $identity */
        $identity = app(
            SensitiveIdentity::class
        );

        $nik = '3576010101019001';
        $nkk = '3576019999999001';

        $kpmId = (int) DB::table(
            'kpms'
        )->insertGetId([
            'nik_hash' => $identity->hash(
                $nik
            ),

            'nik_ciphertext' => $identity->encrypt(
                $nik
            ),

            'nkk_hash' => $identity->hash(
                $nkk
            ),

            'nkk_ciphertext' => $identity->encrypt(
                $nkk
            ),

            'full_name' => 'KPM TEST E-WARUNG',
            'address' => 'ALAMAT TEST',
            'kelurahan' => $kelurahan->name,
            'kecamatan' => $kecamatan->name,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $participantId = (int) DB::table(
            'bpnt_participants'
        )->insertGetId([
            'bpnt_period_id' => $period->id,
            'kpm_id' => $kpmId,
            'kelurahan_id' => $kelurahan->id,
            'bnba_import_id' => $importId,
            'import_row_number' => 1,
            'entitlement_amount' => 200000,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table(
            'transactions'
        )->insert([
            'period_id' => $period->id,
            'bpnt_participant_id' => $participantId,
            'surveyor_id' => $surveyor->id,
            'e_warung_id' => $eWarung->id,
            'participant_kelurahan_id' => $kelurahan->id,
            'surveyor_kelurahan_id' => $kelurahan->id,
            'transacted_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}