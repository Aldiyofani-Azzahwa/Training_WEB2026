<?php

declare(strict_types=1);

namespace Tests\Feature\Surveyor;

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

final class SurveyorMonitoringReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_only_uses_active_period_and_assigned_kelurahan(): void
    {
        $manager = $this->user(
            UserRole::MANAGER
        );

        $surveyor = $this->user(
            UserRole::SURVEYOR,
            'SURVEYOR PRAJURIT KULON'
        );

        $period = $this->activePeriod();

        $prajuritKulon = $this->wilayah(
            'PRAJURIT KULON',
            'PRAJURIT KULON'
        );

        $margorejo = $this->wilayah(
            'MAGERSARI',
            'MARGOREJO'
        );

        $importId = $this->confirmedImport(
            $period,
            $manager
        );

        $taking = $this->participant(
            $period,
            $prajuritKulon,
            $importId,
            '3576010101018001',
            'KPM MENGAMBIL',
            150000
        );

        $deceased = $this->participant(
            $period,
            $prajuritKulon,
            $importId,
            '3576010101018002',
            'KPM MENINGGAL',
            200000
        );

        $pending = $this->participant(
            $period,
            $prajuritKulon,
            $importId,
            '3576010101018003',
            'KPM BELUM MENGAMBIL',
            250000
        );

        $this->participant(
            $period,
            $margorejo,
            $importId,
            '3576010101018004',
            'KPM KELURAHAN LAIN',
            900000
        );

        $assignmentId = $this->assign(
            $period,
            $surveyor,
            $prajuritKulon,
            $manager
        );

        $eWarung = EWarung::query()->create([
            'name' => 'E-Warung Prajurit Kulon',
            'is_active' => true,
        ]);

        $this->transaction(
            $period,
            $surveyor,
            $prajuritKulon,
            $taking,
            $eWarung
        );

        $this->verification(
            $period,
            $surveyor,
            $prajuritKulon,
            $deceased,
            KpmVerificationStatus::DECEASED
        );

        $this->actingAs($surveyor)
            ->getJson(
                '/api/v1/surveyor/monitoring-report'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.assignment.id',
                $assignmentId
            )
            ->assertJsonPath(
                'data.assignment.kelurahan.name',
                'PRAJURIT KULON'
            )
            ->assertJsonPath(
                'data.period.id',
                $period->id
            )
            ->assertJsonPath(
                'data.editable.distribution_assistant_name',
                'SURVEYOR PRAJURIT KULON'
            )
            ->assertJsonPath(
                'data.summary.total_kpm',
                3
            )
            ->assertJsonPath(
                'data.summary.taking',
                1
            )
            ->assertJsonPath(
                'data.summary.deceased',
                1
            )
            ->assertJsonPath(
                'data.summary.pending',
                1
            )
            ->assertJsonPath(
                'data.summary.total_balance',
                600000
            )
            ->assertJsonPath(
                'data.summary.e_warungs.0',
                'E-Warung Prajurit Kulon'
            );

        $this->assertNotSame(
            $pending,
            $taking
        );
    }

    public function test_surveyor_can_edit_only_configuration_and_pdf_does_not_lock_report(): void
    {
        $context = $this->operationalContext();

        $participant = $this->participant(
            $context->period,
            $context->kelurahan,
            $context->importId,
            '3576010101018101',
            'KPM PDF',
            150000
        );

        $this->actingAs($context->surveyor)
            ->putJson(
                '/api/v1/surveyor/monitoring-report',
                [
                    'commodities' => [
                        'Beras',
                        'Telur',
                    ],
                    'social_officer_name' =>
                        'NAMA KASI',
                    'distribution_assistant_name' =>
                        'NAMA PENDAMPING',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.editable.commodities.0',
                'Beras'
            )
            ->assertJsonPath(
                'data.editable.social_officer_name',
                'NAMA KASI'
            );

        $this->assertDatabaseHas(
            'surveyor_monitoring_reports',
            [
                'period_id' =>
                    $context->period->id,
                'assignment_id' =>
                    $context->assignmentId,
                'surveyor_id' =>
                    $context->surveyor->id,
                'kelurahan_id' =>
                    $context->kelurahan->id,
                'social_officer_name' =>
                    'NAMA KASI',
            ]
        );

        $pdfResponse = $this
            ->actingAs($context->surveyor)
            ->get(
                '/api/v1/surveyor/monitoring-report/pdf'
            )
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/pdf'
            );

        $pdf = $pdfResponse->getContent();

        $this->assertStringStartsWith(
            '%PDF-1.4',
            $pdf
        );

        $this->assertStringContainsString(
            '3576********8101',
            $pdf
        );

        $this->assertStringNotContainsString(
            '3576010101018101',
            $pdf
        );

        $this->assertStringContainsString(
            'NURWIDIA KUSUMA DEWI, SE',
            $pdf
        );

        $this->actingAs($context->surveyor)
            ->putJson(
                '/api/v1/surveyor/monitoring-report',
                [
                    'commodities' => [
                        'Beras Premium',
                    ],
                    'social_officer_name' =>
                        'NAMA KASI REVISI',
                    'distribution_assistant_name' =>
                        'NAMA PENDAMPING REVISI',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.editable.commodities.0',
                'Beras Premium'
            );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'user_id' =>
                    $context->surveyor->id,
                'action' =>
                    'surveyor.monitoring_report.pdf_downloaded',
            ]
        );

        $this->assertGreaterThan(
            0,
            $participant
        );
    }

    public function test_automatic_fields_and_foreign_scope_cannot_be_submitted(): void
    {
        $context = $this->operationalContext();

        $this->actingAs($context->surveyor)
            ->putJson(
                '/api/v1/surveyor/monitoring-report',
                [
                    'commodities' => [
                        'Beras',
                    ],
                    'social_officer_name' => null,
                    'distribution_assistant_name' =>
                        $context->surveyor->name,
                    'period_id' => 999,
                    'kelurahan_id' => 999,
                    'summary' => [
                        'total_kpm' => 999,
                    ],
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'period_id',
                'kelurahan_id',
                'summary',
            ]);

        $this->assertDatabaseCount(
            'surveyor_monitoring_reports',
            0
        );
    }

    public function test_only_assigned_surveyor_role_can_access_report(): void
    {
        $context = $this->operationalContext();

        $admin = $this->user(
            UserRole::ADMIN_DINSOS
        );

        $unassignedSurveyor = $this->user(
            UserRole::SURVEYOR
        );

        $this->actingAs($admin)
            ->getJson(
                '/api/v1/surveyor/monitoring-report'
            )
            ->assertForbidden();

        $this->actingAs($unassignedSurveyor)
            ->getJson(
                '/api/v1/surveyor/monitoring-report'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'assignment'
            );

        $this->actingAs($context->surveyor)
            ->getJson(
                '/api/v1/surveyor/monitoring-report'
            )
            ->assertOk();
    }

    private function operationalContext(): object
    {
        $manager = $this->user(
            UserRole::MANAGER
        );

        $surveyor = $this->user(
            UserRole::SURVEYOR,
            'SURVEYOR TEST'
        );

        $period = $this->activePeriod();

        $kelurahan = $this->wilayah(
            'PRAJURIT KULON',
            'PRAJURIT KULON'
        );

        $importId = $this->confirmedImport(
            $period,
            $manager
        );

        $assignmentId = $this->assign(
            $period,
            $surveyor,
            $kelurahan,
            $manager
        );

        return (object) [
            'manager' => $manager,
            'surveyor' => $surveyor,
            'period' => $period,
            'kelurahan' => $kelurahan,
            'importId' => $importId,
            'assignmentId' => $assignmentId,
        ];
    }

    private function user(
        UserRole $role,
        ?string $name = null
    ): User {
        return User::factory()->create([
            'name' => $name
                ?? 'USER '.strtoupper($role->value),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function activePeriod(): BpntPeriod
    {
        return BpntPeriod::query()->create([
            'code' => 'MONITORING-'.uniqid(),
            'name' => 'Maret 2026',
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
        ])->setRelation(
            'kecamatan',
            $kecamatan
        );
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
            'original_name' =>
                'monitoring-test.xlsx',
            'stored_path' =>
                'tests/monitoring-test.xlsx',
            'file_sha256' => hash(
                'sha256',
                uniqid(
                    'monitoring-',
                    true
                )
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
        string $name,
        int $amount
    ): int {
        /** @var SensitiveIdentity $identity */
        $identity = app(
            SensitiveIdentity::class
        );

        $now = now();

        $nkk = '3576019999'
            .substr(
                $nik,
                -6
            );

        $kpmId = DB::table('kpms')
            ->insertGetId([
                'nik_hash' =>
                    $identity->hash($nik),
                'nik_ciphertext' =>
                    $identity->encrypt($nik),
                'nkk_hash' =>
                    $identity->hash($nkk),
                'nkk_ciphertext' =>
                    $identity->encrypt($nkk),
                'full_name' => $name,
                'address' =>
                    'JL. TEST NOMOR 10',
                'rt' => '001',
                'rw' => '002',
                'kelurahan' =>
                    $kelurahan->name,
                'kecamatan' =>
                    $kelurahan
                        ->kecamatan
                        ->name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        return (int) DB::table(
            'bpnt_participants'
        )->insertGetId([
            'bpnt_period_id' =>
                $period->id,
            'kpm_id' => $kpmId,
            'kelurahan_id' =>
                $kelurahan->id,
            'bnba_import_id' =>
                $importId,
            'import_row_number' =>
                $kpmId,
            'entitlement_amount' =>
                $amount,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function assign(
        BpntPeriod $period,
        User $surveyor,
        Kelurahan $kelurahan,
        User $manager
    ): int {
        $now = now();

        return (int) DB::table(
            'surveyor_assignments'
        )->insertGetId([
            'period_id' =>
                $period->id,
            'surveyor_id' =>
                $surveyor->id,
            'kelurahan_id' =>
                $kelurahan->id,
            'assigned_by' =>
                $manager->id,
            'assigned_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function transaction(
        BpntPeriod $period,
        User $surveyor,
        Kelurahan $kelurahan,
        int $participantId,
        EWarung $eWarung
    ): void {
        $now = now();

        DB::table('transactions')->insert([
            'period_id' => $period->id,
            'bpnt_participant_id' =>
                $participantId,
            'surveyor_id' =>
                $surveyor->id,
            'e_warung_id' =>
                $eWarung->id,
            'participant_kelurahan_id' =>
                $kelurahan->id,
            'surveyor_kelurahan_id' =>
                $kelurahan->id,
            'transacted_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function verification(
        BpntPeriod $period,
        User $surveyor,
        Kelurahan $kelurahan,
        int $participantId,
        KpmVerificationStatus $status,
        ?string $reason = null
    ): void {
        $now = now();

        DB::table('kpm_verifications')->insert([
            'period_id' =>
                $period->id,
            'bpnt_participant_id' =>
                $participantId,
            'surveyor_id' =>
                $surveyor->id,
            'participant_kelurahan_id' =>
                $kelurahan->id,
            'surveyor_kelurahan_id' =>
                $kelurahan->id,
            'status' => $status->value,
            'reason' => $reason,
            'active_slot' => 1,
            'verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}