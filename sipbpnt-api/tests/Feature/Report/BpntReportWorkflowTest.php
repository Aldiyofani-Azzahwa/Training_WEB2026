<?php

declare(strict_types=1);

namespace Tests\Feature\Report;

use App\Enums\KpmVerificationStatus;
use App\Enums\UserRole;
use App\Models\BpntPeriod;
use App\Models\EWarung;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\User;
use App\Support\Security\SensitiveIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class BpntReportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_is_draft_until_manager_finalizes_complete_period(): void
    {
        $manager = $this->user(UserRole::MANAGER);
        $admin = $this->user(UserRole::ADMIN_DINSOS);
        $kepalaDinas = $this->user(UserRole::KEPALA_DINAS);
        $surveyor = $this->user(UserRole::SURVEYOR);
        $period = $this->activePeriod();
        $jagalan = $this->wilayah('KRANGGAN', 'JAGALAN');
        $miji = $this->wilayah('PRAJURIT KULON', 'MIJI');
        $importId = $this->confirmedImport($period, $admin);

        $transactedParticipant = $this->participant(
            $period,
            $jagalan,
            $importId,
            '3576010101017001',
            'KPM SUDAH TRANSAKSI'
        );

        $verifiedParticipant = $this->participant(
            $period,
            $miji,
            $importId,
            '3576010101017002',
            'KPM TIDAK MENGAMBIL'
        );

        $verificationId = $this->recordActivity(
            $period,
            $surveyor,
            $manager,
            $jagalan,
            $jagalan,
            $transactedParticipant
        );

        $this->actingAs($manager)
            ->getJson('/api/v1/reports')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status.code', 'draft')
            ->assertJsonPath('data.0.summary.total_kpm', 2)
            ->assertJsonPath('data.0.summary.transacted', 1)
            ->assertJsonPath('data.0.summary.pending', 1)
            ->assertJsonPath('data.0.can_finalize', false);

        $this->actingAs($admin)
            ->getJson('/api/v1/reports')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($manager)
            ->postJson(
                '/api/v1/manager/reports/'.$period->id.'/finalize'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('report');

        $now = now();

        DB::table('kpm_verifications')->insert([
            'period_id' => $period->id,
            'bpnt_participant_id' => $verifiedParticipant,
            'surveyor_id' => $surveyor->id,
            'participant_kelurahan_id' => $miji->id,
            'surveyor_kelurahan_id' => $jagalan->id,
            'status' => KpmVerificationStatus::NOT_CLAIMED->value,
            'reason' => 'KPM menolak mengambil bantuan.',
            'active_slot' => 1,
            'verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $response = $this->actingAs($manager)
            ->postJson(
                '/api/v1/manager/reports/'.$period->id.'/finalize'
            )
            ->assertCreated()
            ->assertJsonPath('data.status.code', 'final')
            ->assertJsonPath('data.summary.pending', 0)
            ->assertJsonPath('data.summary.completion_percentage', 100)
            ->assertJsonPath(
                'data.snapshot.participants.1.wilayah.kelurahan.name',
                'MIJI'
            )
            ->assertJsonPath(
                'data.snapshot.participants.1.resolution.reason',
                'KPM menolak mengambil bantuan.'
            );

        $participant = $response->json(
            'data.snapshot.participants.0'
        );

        $this->assertIsArray($participant);
        $this->assertArrayNotHasKey(
            'outside_assignment',
            $participant
        );

        $this->assertDatabaseHas('bpnt_reports', [
            'period_id' => $period->id,
            'status' => 'final',
            'finalized_by' => $manager->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $manager->id,
            'action' => 'bpnt.report.finalized',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/v1/reports/'.$period->id)
            ->assertOk()
            ->assertJsonPath('data.status.code', 'final');

        $this->actingAs($kepalaDinas)
            ->getJson('/api/v1/reports/'.$period->id)
            ->assertForbidden();

        $this->actingAs($surveyor)
            ->getJson('/api/v1/reports/'.$period->id)
            ->assertForbidden();

        $this->actingAs($admin)
            ->get('/api/v1/reports/'.$period->id.'/excel')
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );

        $this->actingAs($surveyor)
            ->postJson('/api/v1/surveyor/transactions', [
                'bpnt_participant_id' => $transactedParticipant,
                'e_warung_id' => EWarung::query()->value('id'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('report');

        $this->actingAs($manager)
            ->putJson(
                '/api/v1/manager/kpm-verifications/'
                    .$verificationId
                    .'/cancel'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('report');
    }

    private function user(UserRole $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function activePeriod(): BpntPeriod
    {
        return BpntPeriod::query()->create([
            'code' => 'REPORT-'.uniqid(),
            'name' => 'BPNT Agustus 2026',
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

        return Kelurahan::query()->create([
            'kecamatan_id' => $kecamatan->id,
            'code' => 'KEL-'.uniqid(),
            'name' => $kelurahanName,
        ])->setRelation('kecamatan', $kecamatan);
    }

    private function confirmedImport(
        BpntPeriod $period,
        User $uploader
    ): int {
        $now = now();

        return (int) DB::table('bnba_imports')->insertGetId([
            'bpnt_period_id' => $period->id,
            'uploaded_by' => $uploader->id,
            'confirmed_by' => $uploader->id,
            'status' => 'confirmed',
            'original_name' => 'report-test.xlsx',
            'stored_path' => 'tests/report-test.xlsx',
            'file_sha256' => hash(
                'sha256',
                uniqid('report-', true)
            ),
            'total_rows' => 2,
            'valid_rows' => 2,
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
        $identity = app(SensitiveIdentity::class);
        $now = now();
        $nkk = '3576019999'.substr($nik, -6);

        $kpmId = DB::table('kpms')->insertGetId([
            'nik_hash' => $identity->hash($nik),
            'nik_ciphertext' => $identity->encrypt($nik),
            'nkk_hash' => $identity->hash($nkk),
            'nkk_ciphertext' => $identity->encrypt($nkk),
            'full_name' => $name,
            'address' => 'Alamat Test',
            'rt' => '001',
            'rw' => '002',
            'kelurahan' => $kelurahan->name,
            'kecamatan' => $kelurahan->kecamatan->name,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) DB::table('bpnt_participants')->insertGetId([
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

    private function recordActivity(
        BpntPeriod $period,
        User $surveyor,
        User $manager,
        Kelurahan $assignmentKelurahan,
        Kelurahan $verificationKelurahan,
        int $transactionParticipant
    ): int {
        $now = now();

        DB::table('surveyor_assignments')->insert([
            'period_id' => $period->id,
            'surveyor_id' => $surveyor->id,
            'kelurahan_id' => $assignmentKelurahan->id,
            'assigned_by' => $manager->id,
            'assigned_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $eWarung = EWarung::query()->create([
            'name' => 'E-Warung Laporan',
            'is_active' => true,
        ]);

        DB::table('transactions')->insert([
            'period_id' => $period->id,
            'bpnt_participant_id' => $transactionParticipant,
            'surveyor_id' => $surveyor->id,
            'e_warung_id' => $eWarung->id,
            'participant_kelurahan_id' => $assignmentKelurahan->id,
            'surveyor_kelurahan_id' => $assignmentKelurahan->id,
            'transacted_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) DB::table('kpm_verifications')->insertGetId([
            'period_id' => $period->id,
            'bpnt_participant_id' => $transactionParticipant,
            'surveyor_id' => $surveyor->id,
            'participant_kelurahan_id' => $verificationKelurahan->id,
            'surveyor_kelurahan_id' => $assignmentKelurahan->id,
            'status' => KpmVerificationStatus::NOT_CLAIMED->value,
            'reason' => 'Data verifikasi yang telah dibatalkan.',
            'active_slot' => null,
            'verified_at' => $now,
            'cancelled_by' => $manager->id,
            'cancelled_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
