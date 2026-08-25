<?php

declare(strict_types=1);

namespace Tests\Feature\Surveyor;

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

class SurveyorTransactionEntryPointTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_status_and_both_secure_entry_points(): void
    {
        $manager =
            User::factory()
                ->create([
                    'role'
                        => UserRole::MANAGER,

                    'is_active'
                        => true,
                ]);

        $surveyor =
            User::factory()
                ->create([
                    'role'
                        => UserRole::SURVEYOR,

                    'is_active'
                        => true,
                ]);

        $period =
            $this->activePeriod();

        $jagalan =
            $this->wilayah(
                'KRANGGAN',
                'JAGALAN'
            );

        $miji =
            $this->wilayah(
                'PRAJURIT KULON',
                'MIJI'
            );

        $importId =
            $this->confirmedImport(
                $period,
                $manager
            );

        $assignedNik =
            '3576010101012001';

        $outsideNik =
            '3576010101012002';

        $assignedParticipantId =
            $this->participant(
                $period,
                $jagalan,
                $importId,
                $assignedNik,
                'KPM JAGALAN'
            );

        $outsideParticipantId =
            $this->participant(
                $period,
                $miji,
                $importId,
                $outsideNik,
                'KPM MIJI'
            );

        $this->assign(
            $period,
            $surveyor,
            $jagalan,
            $manager
        );

        $eWarung =
            EWarung::query()
                ->create([
                    'name'
                        => 'E-Warung Aktif',

                    'is_active'
                        => true,
                ]);

        /*
         * Sebelum transaksi status masih pending.
         */
        $this
            ->actingAs(
                $surveyor
            )
            ->getJson(
                '/api/v1/surveyor/participants'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.0.id',
                $assignedParticipantId
            )
            ->assertJsonPath(
                'data.0.activity.code',
                'pending'
            )
            ->assertJsonPath(
                'data.0.activity.can_record_transaction',
                true
            );

        /*
         * Halaman KPM dapat mencatat transaksi
         * memakai participant ID.
         */
        $this
            ->actingAs(
                $surveyor
            )
            ->postJson(
                '/api/v1/surveyor/transactions',
                [
                    'bpnt_participant_id'
                        => $assignedParticipantId,

                    'e_warung_id'
                        => $eWarung->id,
                ]
            )
            ->assertCreated()
            ->assertJsonPath(
                'data.participant.id',
                $assignedParticipantId
            );

        /*
         * Setelah transaksi, daftar KPM
         * menampilkan status final.
         */
        $this
            ->actingAs(
                $surveyor
            )
            ->getJson(
                '/api/v1/surveyor/participants'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.0.activity.code',
                'transacted'
            )
            ->assertJsonPath(
                'data.0.activity.can_record_transaction',
                false
            );

        /*
         * Exact NIK lookup KPM yang sama juga
         * harus menunjukkan sudah bertransaksi.
         */
        $this
            ->actingAs(
                $surveyor
            )
            ->postJson(
                '/api/v1/surveyor/lookup-nik',
                [
                    'nik'
                        => $assignedNik,
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.participant.activity.code',
                'transacted'
            )
            ->assertJsonPath(
                'data.participant.activity.can_record_transaction',
                false
            );

        /*
         * Participant ID luar assignment ditolak.
         */
        $this
            ->actingAs(
                $surveyor
            )
            ->postJson(
                '/api/v1/surveyor/transactions',
                [
                    'bpnt_participant_id'
                        => $outsideParticipantId,

                    'e_warung_id'
                        => $eWarung->id,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'bpnt_participant_id'
            );

        /*
         * KPM luar assignment tetap dapat
         * diproses melalui exact NIK.
         */
        $this
            ->actingAs(
                $surveyor
            )
            ->postJson(
                '/api/v1/surveyor/transactions',
                [
                    'nik'
                        => $outsideNik,

                    'e_warung_id'
                        => $eWarung->id,
                ]
            )
            ->assertCreated()
            ->assertJsonPath(
                'data.outside_assignment',
                true
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
                'data.participant.activity.code',
                'transacted'
            );

        /*
         * NIK dan participant ID tidak boleh
         * dikirim bersamaan.
         */
        $this
            ->actingAs(
                $surveyor
            )
            ->postJson(
                '/api/v1/surveyor/transactions',
                [
                    'nik'
                        => $assignedNik,

                    'bpnt_participant_id'
                        => $assignedParticipantId,

                    'e_warung_id'
                        => $eWarung->id,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nik',
                'bpnt_participant_id',
            ]);

        $this->assertDatabaseCount(
            'transactions',
            2
        );
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
                => 'BPNT Agustus 2026',

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
                => 'transaction-entry-point-test.xlsx',

            'stored_path'
                => 'tests/transaction-entry-point-test.xlsx',

            'file_sha256'
                => hash(
                    'sha256',
                    uniqid(
                        'entry-point-',
                        true
                    )
                ),

            'total_rows'
                => 2,

            'valid_rows'
                => 2,

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
        string $name
    ): int {
        /** @var SensitiveIdentity $identity */
        $identity =
            app(
                SensitiveIdentity::class
            );

        $now =
            now();

        $nkk =
            '3576019999'
            . substr(
                $nik,
                -6
            );

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
                => 200000,

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