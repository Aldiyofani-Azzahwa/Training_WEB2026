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

    public function test_manager_can_list_assignments_from_active_period_without_selecting_period(): void
    {
        $manager =
            $this->user(
                UserRole::MANAGER
            );

        [
            $period,
        ] =
            $this->activePeriodWithKelurahans(
                $manager,
                1
            );

        $this
            ->actingAs(
                $manager
            )
            ->getJson(
                '/api/v1/manager/surveyor-assignments'
            )
            ->assertOk()
            ->assertJsonPath(
                'meta.period.id',
                $period->id
            )
            ->assertJsonPath(
                'meta.total_kelurahans',
                1
            )
            ->assertJsonPath(
                'meta.assigned_count',
                0
            )
            ->assertJsonPath(
                'meta.unassigned_count',
                1
            )
            ->assertJsonPath(
                'meta.total_assignments',
                0
            )
            ->assertJsonPath(
                'meta.max_surveyors_per_kelurahan',
                3
            );
    }

    public function test_manager_can_assign_three_surveyors_to_same_kelurahan(): void
    {
        $manager =
            $this->user(
                UserRole::MANAGER
            );

        [
            $period,
            $kelurahans,
        ] =
            $this->activePeriodWithKelurahans(
                $manager,
                1
            );

        $kelurahan =
            $kelurahans[0];

        $surveyors = [
            $this->user(
                UserRole::SURVEYOR
            ),

            $this->user(
                UserRole::SURVEYOR
            ),

            $this->user(
                UserRole::SURVEYOR
            ),
        ];

        foreach (
            $surveyors
            as $surveyor
        ) {
            $this
                ->actingAs(
                    $manager
                )
                ->putJson(
                    '/api/v1/manager/surveyor-assignments',
                    [
                        'kelurahan_id'
                            => $kelurahan->id,

                        'surveyor_id'
                            => $surveyor->id,
                    ]
                )
                ->assertOk()
                ->assertJsonPath(
                    'data.period.id',
                    $period->id
                )
                ->assertJsonPath(
                    'data.kelurahan.id',
                    $kelurahan->id
                )
                ->assertJsonPath(
                    'data.surveyor.id',
                    $surveyor->id
                );
        }

        $this->assertDatabaseCount(
            'surveyor_assignments',
            3
        );
    }

    public function test_fourth_surveyor_is_rejected_for_same_kelurahan(): void
    {
        $manager =
            $this->user(
                UserRole::MANAGER
            );

        [
            ,
            $kelurahans,
        ] =
            $this->activePeriodWithKelurahans(
                $manager,
                1
            );

        $kelurahan =
            $kelurahans[0];

        $surveyors = [
            $this->user(
                UserRole::SURVEYOR
            ),

            $this->user(
                UserRole::SURVEYOR
            ),

            $this->user(
                UserRole::SURVEYOR
            ),

            $this->user(
                UserRole::SURVEYOR
            ),
        ];

        foreach (
            array_slice(
                $surveyors,
                0,
                3
            )
            as $surveyor
        ) {
            $this
                ->actingAs(
                    $manager
                )
                ->putJson(
                    '/api/v1/manager/surveyor-assignments',
                    [
                        'kelurahan_id'
                            => $kelurahan->id,

                        'surveyor_id'
                            => $surveyor->id,
                    ]
                )
                ->assertOk();
        }

        $this
            ->actingAs(
                $manager
            )
            ->putJson(
                '/api/v1/manager/surveyor-assignments',
                [
                    'kelurahan_id'
                        => $kelurahan->id,

                    'surveyor_id'
                        => $surveyors[3]->id,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'kelurahan_id'
            );

        $this->assertDatabaseCount(
            'surveyor_assignments',
            3
        );
    }

    public function test_same_surveyor_cannot_be_assigned_to_two_kelurahans_in_same_period(): void
    {
        $manager =
            $this->user(
                UserRole::MANAGER
            );

        [
            $period,
            $kelurahans,
        ] =
            $this->activePeriodWithKelurahans(
                $manager,
                2
            );

        $surveyor =
            $this->user(
                UserRole::SURVEYOR
            );

        $this
            ->actingAs(
                $manager
            )
            ->putJson(
                '/api/v1/manager/surveyor-assignments',
                [
                    'kelurahan_id'
                        => $kelurahans[0]->id,

                    'surveyor_id'
                        => $surveyor->id,
                ]
            )
            ->assertOk();

        $this
            ->actingAs(
                $manager
            )
            ->putJson(
                '/api/v1/manager/surveyor-assignments',
                [
                    'kelurahan_id'
                        => $kelurahans[1]->id,

                    'surveyor_id'
                        => $surveyor->id,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'surveyor_id'
            );

        $this->assertDatabaseHas(
            'surveyor_assignments',
            [
                'period_id'
                    => $period->id,

                'kelurahan_id'
                    => $kelurahans[0]->id,

                'surveyor_id'
                    => $surveyor->id,
            ]
        );

        $this->assertDatabaseMissing(
            'surveyor_assignments',
            [
                'period_id'
                    => $period->id,

                'kelurahan_id'
                    => $kelurahans[1]->id,

                'surveyor_id'
                    => $surveyor->id,
            ]
        );
    }

    public function test_period_id_from_client_cannot_override_active_period(): void
    {
        $manager =
            $this->user(
                UserRole::MANAGER
            );

        [
            $activePeriod,
            $activeKelurahans,
        ] =
            $this->activePeriodWithKelurahans(
                $manager,
                1
            );

        $inactivePeriod =
            BpntPeriod::query()
                ->create([
                    'code'
                        => 'INACTIVE-'
                            . uniqid(),

                    'name'
                        => 'Periode Nonaktif',

                    'year'
                        => 2025,

                    'is_active'
                        => false,

                    'active_slot'
                        => null,
                ]);

        $surveyor =
            $this->user(
                UserRole::SURVEYOR
            );

        $this
            ->actingAs(
                $manager
            )
            ->putJson(
                '/api/v1/manager/surveyor-assignments',
                [
                    /*
                     * Sengaja dikirim untuk
                     * mencoba manipulasi.
                     *
                     * Backend wajib mengabaikannya.
                     */
                    'period_id'
                        => $inactivePeriod->id,

                    'kelurahan_id'
                        => $activeKelurahans[0]->id,

                    'surveyor_id'
                        => $surveyor->id,
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.period.id',
                $activePeriod->id
            );

        $this->assertDatabaseHas(
            'surveyor_assignments',
            [
                'period_id'
                    => $activePeriod->id,

                'surveyor_id'
                    => $surveyor->id,
            ]
        );

        $this->assertDatabaseMissing(
            'surveyor_assignments',
            [
                'period_id'
                    => $inactivePeriod->id,

                'surveyor_id'
                    => $surveyor->id,
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
            ,
            $kelurahans,
        ] =
            $this->activePeriodWithKelurahans(
                $manager,
                1
            );

        $this
            ->actingAs(
                $manager
            )
            ->putJson(
                '/api/v1/manager/surveyor-assignments',
                [
                    'kelurahan_id'
                        => $kelurahans[0]->id,

                    'surveyor_id'
                        => $surveyor->id,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'surveyor_id'
            );
    }

    public function test_manager_cannot_assign_when_no_period_is_active(): void
    {
        $manager =
            $this->user(
                UserRole::MANAGER
            );

        $surveyor =
            $this->user(
                UserRole::SURVEYOR
            );

        $kecamatan =
            Kecamatan::query()
                ->create([
                    'code'
                        => 'NOACTIVE',

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

        $this
            ->actingAs(
                $manager
            )
            ->putJson(
                '/api/v1/manager/surveyor-assignments',
                [
                    'kelurahan_id'
                        => $kelurahan->id,

                    'surveyor_id'
                        => $surveyor->id,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'period'
            );
    }

    public function test_deleted_assignment_makes_surveyor_available_for_another_kelurahan(): void
    {
        $manager =
            $this->user(
                UserRole::MANAGER
            );

        [
            $period,
            $kelurahans,
        ] =
            $this->activePeriodWithKelurahans(
                $manager,
                2
            );

        $surveyor =
            $this->user(
                UserRole::SURVEYOR
            );

        $created =
            $this
                ->actingAs(
                    $manager
                )
                ->putJson(
                    '/api/v1/manager/surveyor-assignments',
                    [
                        'kelurahan_id'
                            => $kelurahans[0]->id,

                        'surveyor_id'
                            => $surveyor->id,
                    ]
                )
                ->assertOk();

        $assignmentId =
            (int) $created
                ->json(
                    'data.id'
                );

        $this
            ->actingAs(
                $manager
            )
            ->deleteJson(
                "/api/v1/manager/surveyor-assignments/{$assignmentId}"
            )
            ->assertOk();

        $this
            ->actingAs(
                $manager
            )
            ->putJson(
                '/api/v1/manager/surveyor-assignments',
                [
                    'kelurahan_id'
                        => $kelurahans[1]->id,

                    'surveyor_id'
                        => $surveyor->id,
                ]
            )
            ->assertOk();

        $this->assertDatabaseHas(
            'surveyor_assignments',
            [
                'period_id'
                    => $period->id,

                'kelurahan_id'
                    => $kelurahans[1]->id,

                'surveyor_id'
                    => $surveyor->id,
            ]
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
                '/api/v1/manager/surveyor-assignments'
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

    private function activePeriodWithKelurahans(
        User $uploader,
        int $kelurahanCount
    ): array {
        $period =
            BpntPeriod::query()
                ->create([
                    'code'
                        => 'TEST-'
                            . uniqid(),

                    'name'
                        => 'Periode Aktif Test',

                    'year'
                        => 2026,

                    'is_active'
                        => true,

                    'active_slot'
                        => 1,
                ]);

        $kecamatan =
            Kecamatan::query()
                ->create([
                    'code'
                        => 'TEST-KEC-'
                            . uniqid(),

                    'name'
                        => 'KECAMATAN TEST',
                ]);

        $now =
            now();

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
                        => $kelurahanCount,

                    'valid_rows'
                        => $kelurahanCount,

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

        $kelurahans =
            [];

        for (
            $index = 1;
            $index <= $kelurahanCount;
            $index++
        ) {
            $kelurahan =
                Kelurahan::query()
                    ->create([
                        'kecamatan_id'
                            => $kecamatan->id,

                        'code'
                            => str_pad(
                                (string) $index,
                                4,
                                '0',
                                STR_PAD_LEFT
                            ),

                        'name'
                            => 'KELURAHAN TEST '
                                . $index,
                    ]);

            $kpmId =
                DB::table(
                    'kpms'
                )
                    ->insertGetId([
                        'nik_hash'
                            => hash(
                                'sha256',
                                'nik-test-'
                                    . $index
                                    . '-'
                                    . uniqid()
                            ),

                        'nik_ciphertext'
                            => 'encrypted',

                        'nkk_hash'
                            => hash(
                                'sha256',
                                'nkk-test-'
                                    . $index
                                    . '-'
                                    . uniqid()
                            ),

                        'nkk_ciphertext'
                            => 'encrypted',

                        'full_name'
                            => 'KPM TEST '
                                . $index,

                        'address'
                            => 'Alamat Test '
                                . $index,

                        'kelurahan'
                            => $kelurahan
                                ->name,

                        'kecamatan'
                            => $kecamatan
                                ->name,

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
                    => $index,

                'entitlement_amount'
                    => 0,

                'created_at'
                    => $now,

                'updated_at'
                    => $now,
            ]);

            $kelurahans[] =
                $kelurahan;
        }

        return [
            $period->fresh(),
            $kelurahans,
        ];
    }
}