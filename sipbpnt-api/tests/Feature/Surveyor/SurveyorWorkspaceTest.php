<?php

declare(strict_types=1);

namespace Tests\Feature\Surveyor;

use App\Enums\UserRole;
use App\Models\BpntParticipant;
use App\Models\BpntPeriod;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\User;
use App\Support\Security\SensitiveIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SurveyorWorkspaceTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_surveyor_context_uses_active_period_and_own_assignment(): void
    {
        $manager =
            $this->user(
                UserRole::MANAGER
            );

        $surveyor =
            $this->user(
                UserRole::SURVEYOR
            );

        $period =
            $this->activePeriod();

        $jagalan =
            $this->wilayah(
                'KRANGGAN',
                'JAGALAN'
            );

        $importId =
            $this->confirmedImport(
                $period,
                $manager
            );

        $this->participant(
            $period,
            $jagalan,
            $importId,
            '3576010101010001',
            'KPM SATU',
            200000
        );

        $this->participant(
            $period,
            $jagalan,
            $importId,
            '3576010101010002',
            'KPM DUA',
            200000
        );

        $this->assign(
            $period,
            $surveyor,
            $jagalan,
            $manager
        );

        $this
            ->actingAs(
                $surveyor
            )
            ->getJson(
                '/api/v1/surveyor/context'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.period.id',
                $period->id
            )
            ->assertJsonPath(
                'data.assignment.kecamatan.id',
                $jagalan->kecamatan_id
            )
            ->assertJsonPath(
                'data.assignment.kelurahan.id',
                $jagalan->id
            )
            ->assertJsonPath(
                'data.assignment.kelurahan.name',
                'JAGALAN'
            )
            ->assertJsonPath(
                'data.kpm_count',
                2
            );
    }

    public function test_surveyor_participant_list_only_contains_assigned_kelurahan(): void
    {
        $manager =
            $this->user(
                UserRole::MANAGER
            );

        $surveyor =
            $this->user(
                UserRole::SURVEYOR
            );

        $period =
            $this->activePeriod();

        $jagalan =
            $this->wilayah(
                'KRANGGAN',
                'JAGALAN'
            );

        $miji =
            $this->wilayah(
                'KRANGGAN 2',
                'MIJI'
            );

        $importId =
            $this->confirmedImport(
                $period,
                $manager
            );

        $this->participant(
            $period,
            $jagalan,
            $importId,
            '3576010101010003',
            'KPM JAGALAN',
            200000
        );

        $this->participant(
            $period,
            $miji,
            $importId,
            '3576010101010004',
            'KPM MIJI',
            200000
        );

        $this->assign(
            $period,
            $surveyor,
            $jagalan,
            $manager
        );

        $response =
            $this
                ->actingAs(
                    $surveyor
                )
                ->getJson(
                    '/api/v1/surveyor/participants'
                )
                ->assertOk()
                ->assertJsonPath(
                    'meta.total',
                    1
                )
                ->assertJsonPath(
                    'data.0.kpm.full_name',
                    'KPM JAGALAN'
                )
                ->assertJsonPath(
                    'data.0.wilayah.kelurahan.name',
                    'JAGALAN'
                );

        $names =
            collect(
                $response->json(
                    'data'
                )
            )
                ->pluck(
                    'kpm.full_name'
                );

        $this->assertFalse(
            $names->contains(
                'KPM MIJI'
            )
        );
    }

    public function test_exact_nik_lookup_can_find_kpm_outside_assignment(): void
    {
        $manager =
            $this->user(
                UserRole::MANAGER
            );

        $surveyor =
            $this->user(
                UserRole::SURVEYOR
            );

        $period =
            $this->activePeriod();

        $jagalan =
            $this->wilayah(
                'KRANGGAN',
                'JAGALAN'
            );

        $miji =
            $this->wilayah(
                'KRANGGAN 2',
                'MIJI'
            );

        $importId =
            $this->confirmedImport(
                $period,
                $manager
            );

        $outsideNik =
            '3576010101010005';

        $outsideParticipantId =
            $this->participant(
                $period,
                $miji,
                $importId,
                $outsideNik,
                'KPM LUAR WILAYAH',
                200000
            );

        $this->assign(
            $period,
            $surveyor,
            $jagalan,
            $manager
        );

        $this
            ->actingAs(
                $surveyor
            )
            ->postJson(
                '/api/v1/surveyor/lookup-nik',
                [
                    'nik'
                        => $outsideNik,
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.participant.id',
                $outsideParticipantId
            )
            ->assertJsonPath(
                'data.participant.kpm.full_name',
                'KPM LUAR WILAYAH'
            )
            ->assertJsonPath(
                'data.participant.wilayah.kelurahan.name',
                'MIJI'
            )
            ->assertJsonPath(
                'data.scope.outside_assignment',
                true
            )
            ->assertJsonPath(
                'data.scope.label',
                'KPM Luar Wilayah'
            )
            ->assertJsonPath(
                'data.scope.surveyor_kelurahan.name',
                'JAGALAN'
            );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'user_id'
                    => $surveyor->id,

                'action'
                    => 'surveyor.kpm.lookup',

                'auditable_type'
                    => BpntParticipant::class,

                'auditable_id'
                    => $outsideParticipantId,
            ]
        );
    }

    public function test_exact_nik_lookup_marks_own_kelurahan_as_inside_assignment(): void
    {
        $manager =
            $this->user(
                UserRole::MANAGER
            );

        $surveyor =
            $this->user(
                UserRole::SURVEYOR
            );

        $period =
            $this->activePeriod();

        $jagalan =
            $this->wilayah(
                'KRANGGAN',
                'JAGALAN'
            );

        $importId =
            $this->confirmedImport(
                $period,
                $manager
            );

        $nik =
            '3576010101010006';

        $this->participant(
            $period,
            $jagalan,
            $importId,
            $nik,
            'KPM JAGALAN',
            200000
        );

        $this->assign(
            $period,
            $surveyor,
            $jagalan,
            $manager
        );

        $this
            ->actingAs(
                $surveyor
            )
            ->postJson(
                '/api/v1/surveyor/lookup-nik',
                [
                    'nik'
                        => $nik,
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.scope.outside_assignment',
                false
            )
            ->assertJsonPath(
                'data.scope.label',
                'KPM Wilayah JAGALAN'
            )
            ->assertJsonPath(
                'data.participant.saldo_bpnt',
                200000
            );
    }

    public function test_context_is_safe_when_no_active_period_exists(): void
    {
        $surveyor =
            $this->user(
                UserRole::SURVEYOR
            );

        $this
            ->actingAs(
                $surveyor
            )
            ->getJson(
                '/api/v1/surveyor/context'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.period',
                null
            )
            ->assertJsonPath(
                'data.assignment',
                null
            )
            ->assertJsonPath(
                'data.kpm_count',
                0
            );
    }

    public function test_context_is_safe_when_active_period_exists_but_surveyor_has_no_assignment(): void
    {
        $surveyor =
            $this->user(
                UserRole::SURVEYOR
            );

        $period =
            $this->activePeriod();

        $this
            ->actingAs(
                $surveyor
            )
            ->getJson(
                '/api/v1/surveyor/context'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.period.id',
                $period->id
            )
            ->assertJsonPath(
                'data.assignment',
                null
            )
            ->assertJsonPath(
                'data.kpm_count',
                0
            );

        $this
            ->actingAs(
                $surveyor
            )
            ->getJson(
                '/api/v1/surveyor/participants'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'assignment'
            );
    }

    public function test_lookup_requires_exact_sixteen_digit_nik(): void
    {
        $surveyor =
            $this->user(
                UserRole::SURVEYOR
            );

        $this
            ->actingAs(
                $surveyor
            )
            ->postJson(
                '/api/v1/surveyor/lookup-nik',
                [
                    'nik'
                        => '12345',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'nik'
            );
    }

    public function test_non_surveyor_roles_cannot_access_surveyor_workspace(): void
    {
        foreach (
            [
                UserRole::ADMIN_DINSOS,
                UserRole::MANAGER,
                UserRole::KEPALA_DINAS,
            ]
            as $role
        ) {
            $user =
                $this->user(
                    $role
                );

            $this
                ->actingAs(
                    $user
                )
                ->getJson(
                    '/api/v1/surveyor/context'
                )
                ->assertForbidden();
        }
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

    private function activePeriod(): BpntPeriod
    {
        $period =
            new BpntPeriod();

        $period->forceFill([
            'code'
                => 'ACTIVE-'
                    . uniqid(),

            'name'
                => 'BPNT Agustus',

            'year'
                => 2026,

            'is_active'
                => true,

            'active_slot'
                => 1,
        ]);

        $period->save();

        return $period;
    }

    private function wilayah(
        string $kecamatanName,
        string $kelurahanName
    ): Kelurahan {
        /*
         * Helper sekarang hanya mengembalikan
         * Kelurahan.
         *
         * Dengan return type Kelurahan yang
         * eksplisit, VS Code/PHP extension
         * tidak lagi menganggap hasilnya:
         *
         * Kecamatan | Kelurahan
         */
        $kecamatan =
            Kecamatan::query()
                ->create([
                    'code'
                        => 'KEC-'
                            . uniqid(),

                    'name'
                        => $kecamatanName,
                ]);

        $kelurahan =
            Kelurahan::query()
                ->create([
                    'kecamatan_id'
                        => $kecamatan->id,

                    'code'
                        => 'KEL-'
                            . uniqid(),

                    'name'
                        => $kelurahanName,
                ]);

        /*
         * Relation langsung dipasang agar
         * helper participant() tidak perlu
         * query tambahan ketika mengambil:
         *
         * $kelurahan->kecamatan->name
         */
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
        $now =
            now();

        return (int) DB::table(
            'bnba_imports'
        )->insertGetId([
            'bpnt_period_id'
                => $period->id,

            'uploaded_by'
                => $uploader->id,

            'confirmed_by'
                => $uploader->id,

            'status'
                => 'confirmed',

            'original_name'
                => 'surveyor-test.xlsx',

            'stored_path'
                => 'tests/surveyor-test.xlsx',

            'file_sha256'
                => hash(
                    'sha256',
                    'surveyor-test-'
                    . uniqid()
                ),

            'total_rows'
                => 10,

            'valid_rows'
                => 10,

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
    }

    private function participant(
        BpntPeriod $period,
        Kelurahan $kelurahan,
        int $importId,
        string $nik,
        string $name,
        int $saldo
    ): int {
        /** @var SensitiveIdentity $identity */
        $identity =
            app(
                SensitiveIdentity::class
            );

        $nkk =
            '3576019999'
            . substr(
                $nik,
                -6
            );

        $now =
            now();

        $kpmId =
            DB::table(
                'kpms'
            )->insertGetId([
                'nik_hash'
                    => $identity
                        ->hash(
                            $nik
                        ),

                'nik_ciphertext'
                    => $identity
                        ->encrypt(
                            $nik
                        ),

                'nkk_hash'
                    => $identity
                        ->hash(
                            $nkk
                        ),

                'nkk_ciphertext'
                    => $identity
                        ->encrypt(
                            $nkk
                        ),

                'full_name'
                    => $name,

                'address'
                    => 'Alamat Test',

                'kelurahan'
                    => $kelurahan
                        ->name,

                'kecamatan'
                    => $kelurahan
                        ->kecamatan
                        ->name,

                'created_at'
                    => $now,

                'updated_at'
                    => $now,
            ]);

        return (int) DB::table(
            'bpnt_participants'
        )->insertGetId([
            'bpnt_period_id'
                => $period->id,

            'kpm_id'
                => $kpmId,

            'kelurahan_id'
                => $kelurahan->id,

            'bnba_import_id'
                => $importId,

            'import_row_number'
                => $kpmId,

            'entitlement_amount'
                => $saldo,

            'created_at'
                => $now,

            'updated_at'
                => $now,
        ]);
    }

    private function assign(
        BpntPeriod $period,
        User $surveyor,
        Kelurahan $kelurahan,
        User $manager
    ): void {
        $now =
            now();

        DB::table(
            'surveyor_assignments'
        )->insert([
            'period_id'
                => $period->id,

            'surveyor_id'
                => $surveyor->id,

            'kelurahan_id'
                => $kelurahan->id,

            'assigned_by'
                => $manager->id,

            'assigned_at'
                => $now,

            'created_at'
                => $now,

            'updated_at'
                => $now,
        ]);
    }
}