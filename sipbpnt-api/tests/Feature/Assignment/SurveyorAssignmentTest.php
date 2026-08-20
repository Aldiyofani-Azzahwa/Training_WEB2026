<?php

declare(strict_types=1);

namespace Tests\Feature\Assignment;

use App\Enums\UserRole;
use App\Models\BpntPeriod;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SurveyorAssignmentTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_assign_active_surveyor(): void
    {
        $manager =
            $this->user(
                UserRole::MANAGER
            );

        $surveyor =
            $this->user(
                UserRole::SURVEYOR
            );

        [
            $period,
            $kelurahan,
        ] =
            $this->periodWithParticipant(
                $manager
            );

        $this
            ->actingAs(
                $manager
            )
            ->putJson(
                '/api/v1/manager/surveyor-assignments',
                [
                    'period_id'
                        => $period->id,

                    'kelurahan_id'
                        => $kelurahan->id,

                    'surveyor_id'
                        => $surveyor->id,
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.surveyor.id',
                $surveyor->id
            )
            ->assertJsonPath(
                'data.kelurahan.id',
                $kelurahan->id
            );

        $this->assertDatabaseHas(
            'surveyor_assignments',
            [
                'period_id'
                    => $period->id,

                'kelurahan_id'
                    => $kelurahan->id,

                'surveyor_id'
                    => $surveyor->id,

                'assigned_by'
                    => $manager->id,
            ]
        );
    }

    public function test_same_period_and_kelurahan_is_reassigned_not_duplicated(): void
    {
        $manager =
            $this->user(
                UserRole::MANAGER
            );

        $surveyorA =
            $this->user(
                UserRole::SURVEYOR
            );

        $surveyorB =
            $this->user(
                UserRole::SURVEYOR
            );

        [
            $period,
            $kelurahan,
        ] =
            $this->periodWithParticipant(
                $manager
            );

        $payload = [
            'period_id'
                => $period->id,

            'kelurahan_id'
                => $kelurahan->id,

            'surveyor_id'
                => $surveyorA->id,
        ];

        $this
            ->actingAs(
                $manager
            )
            ->putJson(
                '/api/v1/manager/surveyor-assignments',
                $payload
            )
            ->assertOk();

        $payload[
            'surveyor_id'
        ] =
            $surveyorB->id;

        $this
            ->actingAs(
                $manager
            )
            ->putJson(
                '/api/v1/manager/surveyor-assignments',
                $payload
            )
            ->assertOk()
            ->assertJsonPath(
                'data.surveyor.id',
                $surveyorB->id
            );

        $this->assertDatabaseCount(
            'surveyor_assignments',
            1
        );

        $this->assertDatabaseHas(
            'surveyor_assignments',
            [
                'period_id'
                    => $period->id,

                'kelurahan_id'
                    => $kelurahan->id,

                'surveyor_id'
                    => $surveyorB->id,
            ]
        );
    }

    public function test_inactive_surveyor_is_rejected(): void
    {
        $manager =
            $this->user(
                UserRole::MANAGER
            );

        $surveyor =
            $this->user(
                UserRole::SURVEYOR,
                false
            );

        [
            $period,
            $kelurahan,
        ] =
            $this->periodWithParticipant(
                $manager
            );

        $this
            ->actingAs(
                $manager
            )
            ->putJson(
                '/api/v1/manager/surveyor-assignments',
                [
                    'period_id'
                        => $period->id,

                    'kelurahan_id'
                        => $kelurahan->id,

                    'surveyor_id'
                        => $surveyor->id,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'surveyor_id'
            );
    }

    public function test_admin_cannot_manage_assignment(): void
    {
        $admin =
            $this->user(
                UserRole::ADMIN_DINSOS
            );

        $this
            ->actingAs(
                $admin
            )
            ->getJson(
                '/api/v1/manager/surveyor-assignments?period_id=1'
            )
            ->assertForbidden();
    }

    private function user(
        UserRole $role,
        bool $isActive = true
    ): User {
        return User::factory()
            ->create([
                'role'
                    => $role,

                'is_active'
                    => $isActive,
            ]);
    }

    private function periodWithParticipant(
        User $uploader
    ): array {
        $period =
            BpntPeriod::query()
                ->create([
                    'code'
                        => 'TEST-'
                            . uniqid(),

                    'name'
                        => 'Periode Test',

                    'year'
                        => 2026,

                    'is_active'
                        => false,
                ]);

        $kecamatan =
            Kecamatan::query()
                ->create([
                    'code'
                        => 'TEST01',

                    'name'
                        => 'KECAMATAN TEST',
                ]);

        $kelurahan =
            Kelurahan::query()
                ->create([
                    'kecamatan_id'
                        => $kecamatan->id,

                    'code'
                        => '0001',

                    'name'
                        => 'KELURAHAN TEST',
                ]);

        $now =
            now();

        $kpmId =
            DB::table(
                'kpms'
            )
                ->insertGetId([
                    'nik_hash'
                        => hash(
                            'sha256',
                            'nik-test'
                                . uniqid()
                        ),

                    'nik_ciphertext'
                        => 'encrypted',

                    'nkk_hash'
                        => hash(
                            'sha256',
                            'nkk-test'
                                . uniqid()
                        ),

                    'nkk_ciphertext'
                        => 'encrypted',

                    'full_name'
                        => 'KPM TEST',

                    'address'
                        => 'Alamat Test',

                    'kelurahan'
                        => $kelurahan->name,

                    'kecamatan'
                        => $kecamatan->name,

                    'created_at'
                        => $now,

                    'updated_at'
                        => $now,
                ]);

        $importId =
            DB::table(
                'bnba_imports'
            )
                ->insertGetId([
                    'bpnt_period_id'
                        => $period->id,

                    'uploaded_by'
                        => $uploader->id,

                    'confirmed_by'
                        => $uploader->id,

                    'status'
                        => 'confirmed',

                    'original_name'
                        => 'test.xlsx',

                    'stored_path'
                        => 'test/test.xlsx',

                    'file_sha256'
                        => hash(
                            'sha256',
                            'test-file'
                                . uniqid()
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
                        => $now,

                    'created_at'
                        => $now,

                    'updated_at'
                        => $now,
                ]);

        DB::table(
            'bpnt_participants'
        )->insert([
            'bpnt_period_id'
                => $period->id,

            'kpm_id'
                => $kpmId,

            'kelurahan_id'
                => $kelurahan->id,

            'bnba_import_id'
                => $importId,

            'import_row_number'
                => 1,

            'entitlement_amount'
                => 0,

            'created_at'
                => $now,

            'updated_at'
                => $now,
        ]);

        return [
            $period,
            $kelurahan,
        ];
    }
}