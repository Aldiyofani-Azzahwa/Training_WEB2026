<?php

declare(strict_types=1);

namespace Tests\Feature\Bnba;

use App\Enums\BnbaImportStatus;
use App\Enums\UserRole;
use App\Models\BnbaImport;
use App\Models\BpntParticipant;
use App\Models\BpntPeriod;
use App\Models\Kpm;
use App\Models\User;
use App\Support\Security\SensitiveIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Kelurahan;
use Database\Seeders\WilayahSeeder;

class BnbaParticipantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'sipbpnt.identity_hash_key',
            'testing-identity-hash-key-32-bytes'
        );
        $this->seed(
            WilayahSeeder::class
        );
    }

    public function test_manager_can_see_confirmed_bnba_participants(): void
    {
        $manager =
            User::factory()->create([
                'role'
                => UserRole::MANAGER,

                'is_active'
                => true,
            ]);

        $period =
            $this->createConfirmedParticipant();

        $response =
            $this
                ->actingAs($manager)
                ->getJson(
                    '/api/v1/bnba/participants'
                    . '?period_id='
                    . $period->id
                );

        $response
            ->assertOk()
            ->assertJsonPath(
                'meta.total',
                1
            )
            ->assertJsonPath(
                'data.0.kpm.full_name',
                'KPM TEST'
            )
            ->assertJsonPath(
                'data.0.entitlement_amount',
                450000
            );

        $nik =
            (string) 
            $response->json(
                'data.0.kpm.nik'
            );

        $this->assertNotSame(
            '1234567891011132',
            $nik
        );

        $this->assertStringContainsString(
            '********',
            $nik
        );
    }

    public function test_manager_can_search_participant_using_full_nik(): void
    {
        $manager =
            User::factory()->create([
                'role'
                => UserRole::MANAGER,

                'is_active'
                => true,
            ]);

        $period =
            $this->createConfirmedParticipant();

        $response =
            $this
                ->actingAs($manager)
                ->getJson(
                    '/api/v1/bnba/participants'
                    . '?period_id='
                    . $period->id
                    . '&search=1234567891011132'
                );

        $response
            ->assertOk()
            ->assertJsonPath(
                'meta.total',
                1
            );
    }

    public function test_surveyor_cannot_access_management_bnba(): void
    {
        $surveyor =
            User::factory()->create([
                'role'
                => UserRole::SURVEYOR,

                'is_active'
                => true,
            ]);

        $period =
            BpntPeriod::query()->create([
                'code'
                => 'BPNT-2026',

                'name'
                => 'BPNT 2026',

                'year'
                => 2026,

                'is_active'
                => true,
            ]);

        $this
            ->actingAs($surveyor)
            ->getJson(
                '/api/v1/bnba/participants'
                . '?period_id='
                . $period->id
            )
            ->assertForbidden();
    }

    private function createConfirmedParticipant(
    ): BpntPeriod {
        /** @var SensitiveIdentity $identity */
        $identity = app(
            SensitiveIdentity::class
        );

        $admin =
            User::factory()->create([
                'role'
                => UserRole::ADMIN_DINSOS,

                'is_active'
                => true,
            ]);

        $period =
            BpntPeriod::query()->create([
                'code'
                => 'BPNT-2026',

                'name'
                => 'BPNT 2026',

                'year'
                => 2026,

                'is_active'
                => true,
            ]);

        $kpm =
            Kpm::query()->create([
                'nik_hash'
                => $identity->hash(
                        '1234567891011132'
                    ),

                'nik_ciphertext'
                => $identity->encrypt(
                        '1234567891011132'
                    ),

                'nkk_hash'
                => $identity->hash(
                        '1987654321010123'
                    ),

                'nkk_ciphertext'
                => $identity->encrypt(
                        '1987654321010123'
                    ),

                'full_name'
                => 'KPM TEST',

                'birth_place'
                => 'MOJOKERTO',

                'birth_date'
                => '1980-01-01',

                'mother_name'
                => 'IBU TEST',

                'address'
                => 'ALAMAT TEST',

                'rt'
                => '001',

                'rw'
                => '002',

                'kelurahan'
                => 'JAGALAN',

                'kecamatan'
                => 'KRANGGAN',

                'account_ciphertext'
                => $identity->encrypt(
                        '1234566742'
                    ),
            ]);

        $import =
            BnbaImport::query()->create([
                'bpnt_period_id'
                => $period->id,

                'uploaded_by'
                => $admin->id,

                'confirmed_by'
                => $admin->id,

                'status'
                => BnbaImportStatus
                        ::CONFIRMED,

                'original_name'
                => 'bnba-test.xlsx',

                'stored_path'
                => 'bnba-imports/test.xlsx',

                'file_sha256'
                => str_repeat(
                        'a',
                        64
                    ),

                'total_rows'
                => 1,

                'valid_rows'
                => 1,

                'warning_rows'
                => 0,

                'invalid_rows'
                => 0,

                'duplicate_rows'
                => 0,

                'confirmed_at'
                => now(),
            ]);
        $jagalan =
            Kelurahan::query()
                ->where(
                    'name',
                    'Jagalan'
                )
                ->whereHas(
                    'kecamatan',
                    fn($query) =>
                    $query->where(
                        'name',
                        'Kranggan'
                    )
                )
                ->firstOrFail();

        BpntParticipant::query()
            ->create([
                'bpnt_period_id'
                => $period->id,

                'kpm_id'
                => $kpm->id,
                'kelurahan_id'
                => $jagalan->id,

                'bnba_import_id'
                => $import->id,

                'import_row_number'
                => 2,

                'membership_year'
                => '2024',

                'e_warung_name_source'
                => 'E WAROENG TEST',

                'source_status'
                => 'PENGAJUAN 2026',

                'source_description'
                => '1 PENERIMA',

                'monthly_statuses'
                => [],

                'sk_status'
                => 'SK 2026',

                'sk_description'
                => '1 PENERIMA',

                'welfare_rank'
                => 3,

                'entitlement_amount'
                => 450000,
            ]);

        return $period;
    }

    public function test_participant_keeps_period_wilayah_when_kpm_current_wilayah_changes(): void
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

        $period =
            $this
                ->createConfirmedParticipant();

        /*
         * Simulasikan KPM yang pada import
         * periode berikutnya sudah pindah wilayah.
         */
        $kpm =
            Kpm::query()
                ->firstOrFail();

        $kpm->update([
            'kelurahan'
            => 'MENTIKAN',

            'kecamatan'
            => 'PRAJURIT KULON',
        ]);

        /*
         * Participant periode lama tetap harus
         * menunjuk Jagalan - Kranggan.
         */
        $response =
            $this
                ->actingAs(
                    $manager
                )
                ->getJson(
                    '/api/v1/bnba/participants'
                    . '?period_id='
                    . $period->id
                );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.0.kpm.kelurahan',
                'Jagalan'
            )
            ->assertJsonPath(
                'data.0.kpm.kecamatan',
                'Kranggan'
            );
    }
}