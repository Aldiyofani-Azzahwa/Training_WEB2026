<?php

declare(strict_types=1);

namespace Tests\Feature\Manager;

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

final class ManagerTransactionMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_monitor_active_period_transactions(): void
    {
        $manager = $this->user(UserRole::MANAGER);
        $surveyor = $this->user(UserRole::SURVEYOR);
        $idleSurveyor = $this->user(UserRole::SURVEYOR);
        $period = $this->activePeriod();
        $jagalan = $this->wilayah('KRANGGAN', 'JAGALAN');
        $miji = $this->wilayah('PRAJURIT KULON', 'MIJI');
        $importId = $this->confirmedImport($period, $manager);

        $insideParticipant = $this->participant(
            $period,
            $jagalan,
            $importId,
            '3576010101013001',
            'KPM JAGALAN TRANSAKSI'
        );

        $outsideParticipant = $this->participant(
            $period,
            $miji,
            $importId,
            '3576010101013002',
            'KPM MIJI TRANSAKSI'
        );

        $verifiedParticipant = $this->participant(
            $period,
            $jagalan,
            $importId,
            '3576010101013003',
            'KPM TIDAK MENGAMBIL'
        );

        $this->participant(
            $period,
            $jagalan,
            $importId,
            '3576010101013004',
            'KPM BELUM TRANSAKSI'
        );

        $this->assign(
            $period,
            $surveyor,
            $jagalan,
            $manager
        );

        $this->assign(
            $period,
            $idleSurveyor,
            $miji,
            $manager
        );

        $eWarung = EWarung::query()->create([
            'name' => 'E-Warung Makmur',
            'is_active' => true,
        ]);

        $now = now();

        DB::table('transactions')->insert([
            [
                'period_id' => $period->id,
                'bpnt_participant_id' => $insideParticipant,
                'surveyor_id' => $surveyor->id,
                'e_warung_id' => $eWarung->id,
                'participant_kelurahan_id' => $jagalan->id,
                'surveyor_kelurahan_id' => $jagalan->id,
                'transacted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'period_id' => $period->id,
                'bpnt_participant_id' => $outsideParticipant,
                'surveyor_id' => $surveyor->id,
                'e_warung_id' => $eWarung->id,
                'participant_kelurahan_id' => $miji->id,
                'surveyor_kelurahan_id' => $jagalan->id,
                'transacted_at' => $now->copy()->addMinute(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('kpm_verifications')->insert([
            'period_id' => $period->id,
            'bpnt_participant_id' => $verifiedParticipant,
            'surveyor_id' => $surveyor->id,
            'participant_kelurahan_id' => $jagalan->id,
            'surveyor_kelurahan_id' => $jagalan->id,
            'status' => KpmVerificationStatus::NOT_CLAIMED->value,
            'reason' => 'KPM menolak mengambil bantuan.',
            'active_slot' => 1,
            'verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->actingAs($manager)
            ->getJson('/api/v1/manager/transaction-monitoring')
            ->assertOk()
            ->assertJsonPath('data.period.id', $period->id)
            ->assertJsonPath('data.summary.total_kpm', 4)
            ->assertJsonPath('data.summary.transacted', 2)
            ->assertJsonPath('data.summary.pending', 1)
            ->assertJsonPath('data.summary.active_verifications', 1)
            ->assertJsonPath('data.summary.not_claimed', 1)
            ->assertJsonPath('data.summary.outside_assignment', 1)
            ->assertJsonPath('data.summary.completion_percentage', 75)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonCount(2, 'data.transactions')
            ->assertJsonPath(
                'data.transactions.0.outside_assignment',
                true
            )
            ->assertJsonPath(
                'data.breakdowns.e_warungs.0.transactions',
                2
            )
            ->assertJsonPath(
                'data.breakdowns.surveyors.0.transactions',
                2
            );

        $this->actingAs($manager)
            ->getJson(
                '/api/v1/manager/transaction-monitoring?outside_assignment=1'
            )
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath(
                'data.transactions.0.participant.id',
                $outsideParticipant
            );

        $this->actingAs($manager)
            ->getJson(
                '/api/v1/manager/transaction-monitoring?outside_assignment=0'
            )
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath(
                'data.transactions.0.participant.id',
                $insideParticipant
            );

        $this->actingAs($manager)
            ->getJson(
                '/api/v1/manager/transaction-monitoring?search=3576010101013001'
            )
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath(
                'data.transactions.0.participant.id',
                $insideParticipant
            );
    }

    public function test_monitoring_uses_safe_state_without_active_period(): void
    {
        $manager = $this->user(UserRole::MANAGER);

        $this->actingAs($manager)
            ->getJson('/api/v1/manager/transaction-monitoring')
            ->assertOk()
            ->assertJsonPath('data.period', null)
            ->assertJsonPath('data.summary.total_kpm', 0)
            ->assertJsonPath('meta.total', 0)
            ->assertJsonCount(0, 'data.transactions');
    }

    public function test_non_manager_cannot_access_transaction_monitoring(): void
    {
        $surveyor = $this->user(UserRole::SURVEYOR);
        $admin = $this->user(UserRole::ADMIN_DINSOS);

        $this->actingAs($surveyor)
            ->getJson('/api/v1/manager/transaction-monitoring')
            ->assertForbidden();

        $this->actingAs($admin)
            ->getJson('/api/v1/manager/transaction-monitoring')
            ->assertForbidden();
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
            'code' => 'ACTIVE-'.uniqid(),
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
            'original_name' => 'monitoring-test.xlsx',
            'stored_path' => 'tests/monitoring-test.xlsx',
            'file_sha256' => hash('sha256', uniqid('monitoring-', true)),
            'total_rows' => 4,
            'valid_rows' => 4,
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
        $nkk = '3576019999'.substr($nik, -6);
        $now = now();

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

    private function assign(
        BpntPeriod $period,
        User $surveyor,
        Kelurahan $kelurahan,
        User $manager
    ): void {
        $now = now();

        DB::table('surveyor_assignments')->insert([
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