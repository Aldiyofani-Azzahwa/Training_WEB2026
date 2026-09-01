<?php

declare(strict_types=1);

namespace Tests\Feature\Surveyor;

use App\Enums\KpmVerificationStatus;
use App\Enums\UserRole;
use App\Models\BpntPeriod;
use App\Models\EWarung;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\KpmVerification;
use App\Models\User;
use App\Support\Security\SensitiveIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SurveyorKpmActivityTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_uses_exact_nik_and_allows_kpm_outside_assignment(): void
    {
        $manager = $this->user(UserRole::MANAGER);
        $surveyor = $this->user(UserRole::SURVEYOR);
        $period = $this->activePeriod();
        $jagalan = $this->wilayah('KRANGGAN', 'JAGALAN');
        $miji = $this->wilayah('PRAJURIT KULON', 'MIJI');
        $importId = $this->confirmedImport($period, $manager);
        $nik = '3576010101011001';

        $participantId = $this->participant(
            $period,
            $miji,
            $importId,
            $nik,
            'KPM MIJI'
        );

        $this->assign(
            $period,
            $surveyor,
            $jagalan,
            $manager
        );

        $eWarung = EWarung::query()->create([
            'name' => 'E-Warung Aktif',
            'is_active' => true,
        ]);

        $this->actingAs($surveyor)
            ->postJson('/api/v1/surveyor/transactions', [
                'nik' => $nik,
                'e_warung_id' => $eWarung->id,
            ])
            ->assertCreated()
            ->assertJsonPath(
                'data.status.code',
                'transacted'
            )
            ->assertJsonPath(
                'data.status.label',
                'Sudah Bertransaksi'
            )
            ->assertJsonPath(
                'data.outside_assignment',
                true
            )
            ->assertJsonMissingPath('data.nominal')
            ->assertJsonMissingPath('data.sisa_saldo');

        $this->assertDatabaseHas('transactions', [
            'period_id' => $period->id,
            'bpnt_participant_id' => $participantId,
            'surveyor_id' => $surveyor->id,
            'e_warung_id' => $eWarung->id,
            'participant_kelurahan_id' => $miji->id,
            'surveyor_kelurahan_id' => $jagalan->id,
        ]);

        $this->assertFalse(
            Schema::hasColumn(
                'transactions',
                'nominal'
            )
        );

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $surveyor->id,
            'action' => 'surveyor.transaction.created',
        ]);
    }

    public function test_one_kpm_can_only_have_one_transaction_in_one_period(): void
    {
        $context = $this->operationalContext();
        $nik = '3576010101011002';

        $this->participant(
            $context->period,
            $context->kelurahan,
            $context->importId,
            $nik,
            'KPM SATU'
        );

        $eWarung = EWarung::query()->create([
            'name' => 'E-Warung Satu',
            'is_active' => true,
        ]);

        $payload = [
            'nik' => $nik,
            'e_warung_id' => $eWarung->id,
        ];

        $this->actingAs($context->surveyor)
            ->postJson(
                '/api/v1/surveyor/transactions',
                $payload
            )
            ->assertCreated();

        $this->actingAs($context->surveyor)
            ->postJson(
                '/api/v1/surveyor/transactions',
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('kpm');

        $this->assertDatabaseCount(
            'transactions',
            1
        );
    }

    public function test_transaction_rejects_nominal_and_inactive_e_warung(): void
    {
        $context = $this->operationalContext();
        $nik = '3576010101011003';

        $this->participant(
            $context->period,
            $context->kelurahan,
            $context->importId,
            $nik,
            'KPM DUA'
        );

        $inactive = EWarung::query()->create([
            'name' => 'E-Warung Nonaktif',
            'is_active' => false,
        ]);

        $this->actingAs($context->surveyor)
            ->postJson('/api/v1/surveyor/transactions', [
                'nik' => $nik,
                'e_warung_id' => $inactive->id,
                'nominal' => 200000,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nominal');

        $this->actingAs($context->surveyor)
            ->postJson('/api/v1/surveyor/transactions', [
                'nik' => $nik,
                'e_warung_id' => $inactive->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('e_warung_id');

        $this->assertDatabaseCount(
            'transactions',
            0
        );
    }

    public function test_not_claimed_requires_reason_but_other_statuses_do_not(): void
    {
        $context = $this->operationalContext();

        $firstId = $this->participant(
            $context->period,
            $context->kelurahan,
            $context->importId,
            '3576010101011004',
            'KPM TIGA'
        );

        $secondId = $this->participant(
            $context->period,
            $context->kelurahan,
            $context->importId,
            '3576010101011005',
            'KPM EMPAT'
        );

        $this->actingAs($context->surveyor)
            ->postJson(
                '/api/v1/surveyor/kpm-verifications',
                [
                    'bpnt_participant_id' => $firstId,
                    'status'
                        => KpmVerificationStatus::NOT_CLAIMED->value,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this->actingAs($context->surveyor)
            ->postJson(
                '/api/v1/surveyor/kpm-verifications',
                [
                    'bpnt_participant_id' => $firstId,
                    'status'
                        => KpmVerificationStatus::NOT_CLAIMED->value,
                    'reason'
                        => 'KPM menolak mengambil bantuan pada periode ini.',
                ]
            )
            ->assertCreated()
            ->assertJsonPath(
                'data.status.label',
                'Tidak Transaksi'
            )
            ->assertJsonPath(
                'data.reason',
                'KPM menolak mengambil bantuan pada periode ini.'
            );

        $this->actingAs($context->surveyor)
            ->postJson(
                '/api/v1/surveyor/kpm-verifications',
                [
                    'bpnt_participant_id' => $secondId,
                    'status'
                        => KpmVerificationStatus::DECEASED->value,
                ]
            )
            ->assertCreated()
            ->assertJsonPath(
                'data.status.label',
                'Meninggal'
            )
            ->assertJsonPath(
                'data.reason',
                null
            );
    }

    public function test_surveyor_cannot_verify_kpm_outside_assignment(): void
    {
        $context = $this->operationalContext();
        $miji = $this->wilayah(
            'PRAJURIT KULON',
            'MIJI'
        );

        $outsideId = $this->participant(
            $context->period,
            $miji,
            $context->importId,
            '3576010101011006',
            'KPM LUAR'
        );

        $this->actingAs($context->surveyor)
            ->postJson(
                '/api/v1/surveyor/kpm-verifications',
                [
                    'bpnt_participant_id' => $outsideId,
                    'status'
                        => KpmVerificationStatus::MOVED_DOMICILE->value,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'bpnt_participant_id'
            );

        $this->assertDatabaseCount(
            'kpm_verifications',
            0
        );
    }

    public function test_transaction_and_active_verification_are_mutually_exclusive(): void
    {
        $context = $this->operationalContext();
        $nik = '3576010101011007';

        $participantId = $this->participant(
            $context->period,
            $context->kelurahan,
            $context->importId,
            $nik,
            'KPM LIMA'
        );

        $eWarung = EWarung::query()->create([
            'name' => 'E-Warung Dua',
            'is_active' => true,
        ]);

        $this->actingAs($context->surveyor)
            ->postJson(
                '/api/v1/surveyor/kpm-verifications',
                [
                    'bpnt_participant_id' => $participantId,
                    'status'
                        => KpmVerificationStatus::MOVED_DOMICILE->value,
                ]
            )
            ->assertCreated();

        $this->actingAs($context->surveyor)
            ->postJson(
                '/api/v1/surveyor/transactions',
                [
                    'nik' => $nik,
                    'e_warung_id' => $eWarung->id,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('kpm');

        $this->assertDatabaseCount(
            'transactions',
            0
        );

        $this->assertDatabaseCount(
            'kpm_verifications',
            1
        );
    }

    public function test_manager_can_cancel_verification_and_kpm_becomes_pending_again(): void
    {
        $context = $this->operationalContext();

        $participantId = $this->participant(
            $context->period,
            $context->kelurahan,
            $context->importId,
            '3576010101011008',
            'KPM ENAM'
        );

        $verificationResponse = $this
            ->actingAs($context->surveyor)
            ->postJson(
                '/api/v1/surveyor/kpm-verifications',
                [
                    'bpnt_participant_id' => $participantId,
                    'status'
                        => KpmVerificationStatus::DECEASED->value,
                ]
            )
            ->assertCreated();

        $verificationId = (int) $verificationResponse
            ->json('data.id');

        $this->actingAs($context->surveyor)
            ->getJson(
                '/api/v1/surveyor/pending-participants'
            )
            ->assertOk()
            ->assertJsonPath(
                'meta.total',
                0
            );

        $this->actingAs($context->manager)
            ->putJson(
                "/api/v1/manager/kpm-verifications/{$verificationId}/cancel"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.is_cancelled',
                true
            )
            ->assertJsonPath(
                'data.cancelled_by.id',
                $context->manager->id
            );

        $this->assertDatabaseHas(
            'kpm_verifications',
            [
                'id' => $verificationId,
                'active_slot' => null,
                'cancelled_by'
                    => $context->manager->id,
            ]
        );

        $this->actingAs($context->surveyor)
            ->getJson(
                '/api/v1/surveyor/pending-participants'
            )
            ->assertOk()
            ->assertJsonPath(
                'meta.total',
                1
            )
            ->assertJsonPath(
                'data.0.id',
                $participantId
            );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'user_id' => $context->manager->id,
                'action'
                    => 'manager.kpm_verification.cancelled',
                'auditable_type'
                    => KpmVerification::class,
                'auditable_id'
                    => $verificationId,
            ]
        );
    }

    public function test_surveyor_cannot_cancel_verification_and_other_roles_cannot_create_activity(): void
    {
        $context = $this->operationalContext();
        $admin = $this->user(
            UserRole::ADMIN_DINSOS
        );

        $participantId = $this->participant(
            $context->period,
            $context->kelurahan,
            $context->importId,
            '3576010101011009',
            'KPM TUJUH'
        );

        $verificationResponse = $this
            ->actingAs($context->surveyor)
            ->postJson(
                '/api/v1/surveyor/kpm-verifications',
                [
                    'bpnt_participant_id' => $participantId,
                    'status'
                        => KpmVerificationStatus::DECEASED->value,
                ]
            )
            ->assertCreated();

        $verificationId = (int) $verificationResponse
            ->json('data.id');

        $this->actingAs($context->surveyor)
            ->putJson(
                "/api/v1/manager/kpm-verifications/{$verificationId}/cancel"
            )
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson(
                '/api/v1/surveyor/kpm-verifications',
                [
                    'bpnt_participant_id' => $participantId,
                    'status'
                        => KpmVerificationStatus::DECEASED->value,
                ]
            )
            ->assertForbidden();
    }

    private function operationalContext(): ActivityTestContext
    {
        $manager = $this->user(
            UserRole::MANAGER
        );

        $surveyor = $this->user(
            UserRole::SURVEYOR
        );

        $period = $this->activePeriod();

        $jagalan = $this->wilayah(
            'KRANGGAN',
            'JAGALAN'
        );

        $importId = $this->confirmedImport(
            $period,
            $manager
        );

        $this->assign(
            $period,
            $surveyor,
            $jagalan,
            $manager
        );

        return new ActivityTestContext(
            manager: $manager,
            surveyor: $surveyor,
            period: $period,
            kelurahan: $jagalan,
            importId: $importId,
        );
    }

    private function user(
        UserRole $role
    ): User {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function activePeriod(): BpntPeriod
    {
        return BpntPeriod::query()->create([
            'code' => 'ACTIVE-'.uniqid(),
            'name' => 'BPNT Agustus',
            'year' => 2026,
            'is_active' => true,
            'active_slot' => 1,
        ]);
    }

    private function wilayah(
        string $kecamatanName,
        string $kelurahanName
    ): Kelurahan {
        $kecamatan = Kecamatan::query()->create([
            'code' => 'KEC-'.uniqid(),
            'name' => $kecamatanName,
        ]);

        $kelurahan = Kelurahan::query()->create([
            'kecamatan_id' => $kecamatan->id,
            'code' => 'KEL-'.uniqid(),
            'name' => $kelurahanName,
        ]);

        $kelurahan->setRelation(
            'kecamatan',
            $kecamatan
        );

        return $kelurahan;
    }

    private function confirmedImport(
        BpntPeriod $period,
        User $uploader
    ): int {
        $now = now();

        return (int) DB::table(
            'bnba_imports'
        )->insertGetId([
            'bpnt_period_id' => $period->id,
            'uploaded_by' => $uploader->id,
            'confirmed_by' => $uploader->id,
            'status' => 'confirmed',
            'original_name' => 'activity-test.xlsx',
            'stored_path' => 'tests/activity-test.xlsx',
            'file_sha256'
                => hash(
                    'sha256',
                    'activity-'.uniqid()
                ),
            'total_rows' => 10,
            'valid_rows' => 10,
            'warning_rows' => 0,
            'invalid_rows' => 0,
            'duplicate_rows' => 0,
            'confirmed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function participant(
        BpntPeriod $period,
        Kelurahan $kelurahan,
        int $importId,
        string $nik,
        string $name
    ): int {
        /** @var SensitiveIdentity $identity */
        $identity = app(
            SensitiveIdentity::class
        );

        $nkk = '3576019999'.substr(
            $nik,
            -6
        );

        $now = now();

        $kpmId = DB::table(
            'kpms'
        )->insertGetId([
            'nik_hash'
                => $identity->hash($nik),

            'nik_ciphertext'
                => $identity->encrypt($nik),

            'nkk_hash'
                => $identity->hash($nkk),

            'nkk_ciphertext'
                => $identity->encrypt($nkk),

            'full_name' => $name,
            'address' => 'Alamat Test',
            'kelurahan' => $kelurahan->name,
            'kecamatan'
                => $kelurahan->kecamatan->name,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) DB::table(
            'bpnt_participants'
        )->insertGetId([
            'bpnt_period_id' => $period->id,
            'kpm_id' => $kpmId,
            'kelurahan_id' => $kelurahan->id,
            'bnba_import_id' => $importId,
            'import_row_number' => $kpmId,
            'entitlement_amount' => 200000,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function assign(
        BpntPeriod $period,
        User $surveyor,
        Kelurahan $kelurahan,
        User $manager
    ): void {
        $now = now();

        DB::table(
            'surveyor_assignments'
        )->insert([
            'period_id' => $period->id,
            'surveyor_id' => $surveyor->id,
            'kelurahan_id' => $kelurahan->id,
            'assigned_by' => $manager->id,
            'assigned_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

final readonly class ActivityTestContext
{
    public function __construct(
        public User $manager,
        public User $surveyor,
        public BpntPeriod $period,
        public Kelurahan $kelurahan,
        public int $importId,
    ) {}
}