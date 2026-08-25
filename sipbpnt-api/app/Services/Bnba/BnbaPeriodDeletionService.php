<?php

declare(strict_types=1);

namespace App\Services\Bnba;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\BnbaImportRepositoryInterface;
use App\Contracts\Repositories\BpntParticipantRepositoryInterface;
use App\Contracts\Repositories\BpntPeriodRepositoryInterface;
use App\Contracts\Repositories\SurveyorAssignmentRepositoryInterface;
use App\Models\BpntPeriod;
use App\Models\SurveyorAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class BnbaPeriodDeletionService
{
    public function __construct(
        private readonly BpntPeriodRepositoryInterface $periods,
        private readonly BnbaImportRepositoryInterface $imports,
        private readonly BpntParticipantRepositoryInterface $participants,
        private readonly SurveyorAssignmentRepositoryInterface $assignments,
        private readonly AuditLogRepositoryInterface $auditLogs,
    ) {
    }

    /**
     * Menghapus seluruh data BNBA pada satu periode nonaktif.
     *
     * Assignment, participant, import, dan audit log diproses
     * dalam satu database transaction.
     *
     * File Excel baru dihapus setelah transaction berhasil commit.
     *
     * @return array{
     *     imports_deleted: int,
     *     participants_deleted: int,
     *     assignments_deleted: int
     * }
     */
    public function deleteForPeriod(
        int $periodId,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): array {
        $result =
            DB::transaction(
                function () use (
                    $periodId,
                    $actor,
                    $ipAddress,
                    $userAgent
                ): array {
                    /*
                     * Kunci row periode.
                     *
                     * Lock ini mencegah periode diaktifkan
                     * ketika proses penghapusan BNBA
                     * sedang berlangsung.
                     */
                    $period =
                        $this->periods
                            ->findForUpdate(
                                $periodId
                            );

                    $this->assertPeriodCanBeDeleted(
                        $period
                    );

                    /*
                     * Kunci seluruh import pada periode.
                     */
                    $imports =
                        $this->imports
                            ->forPeriodForUpdate(
                                $period->id
                            );

                    if (
                        $imports->isEmpty()
                    ) {
                        throw ValidationException
                            ::withMessages([
                                'bnba' => [
                                    'Periode ini belum memiliki data BNBA.',
                                ],
                            ]);
                    }

                    $storedPaths =
                        $imports
                            ->pluck(
                                'stored_path'
                            )
                            ->filter(
                                static fn ($path): bool =>
                                    is_string($path)
                                    && trim($path) !== ''
                            )
                            ->map(
                                static fn ($path): string =>
                                    (string) $path
                            )
                            ->values()
                            ->all();

                    $importIds =
                        $imports
                            ->pluck('id')
                            ->map(
                                static fn ($id): int =>
                                    (int) $id
                            )
                            ->values()
                            ->all();

                    /*
                     * Assignment periode nonaktif harus
                     * dibersihkan agar tidak menempel
                     * pada BNBA pengganti.
                     */
                    $periodAssignments =
                        $this->assignments
                            ->forPeriod(
                                $period->id
                            );

                    $assignmentsDeleted = 0;

                    foreach (
                        $periodAssignments
                        as $assignment
                    ) {
                        $this->recordAssignmentDeletion(
                            $assignment,
                            $actor,
                            $ipAddress,
                            $userAgent
                        );

                        $this->assignments
                            ->delete(
                                $assignment
                            );

                        $assignmentsDeleted++;
                    }

                    /*
                     * Participant harus dihapus sebelum import
                     * karena foreign key menggunakan restrict.
                     */
                    $participantsDeleted =
                        $this->participants
                            ->deleteForPeriod(
                                $period->id
                            );

                    /*
                     * Baris preview BNBA otomatis terhapus
                     * melalui foreign key cascade.
                     */
                    $importsDeleted =
                        $this->imports
                            ->deleteForPeriod(
                                $period->id
                            );

                    $this->auditLogs
                        ->record([
                            'user_id'
                                => $actor->id,

                            'action'
                                => 'bnba.period_data.deleted',

                            'auditable_type'
                                => BpntPeriod::class,

                            'auditable_id'
                                => $period->id,

                            'metadata' => [
                                'period_name'
                                    => $period->name,

                                'import_ids'
                                    => $importIds,

                                'imports_deleted'
                                    => $importsDeleted,

                                'participants_deleted'
                                    => $participantsDeleted,

                                'assignments_deleted'
                                    => $assignmentsDeleted,
                            ],

                            'ip_address'
                                => $ipAddress,

                            'user_agent'
                                => $userAgent,
                        ]);

                    return [
                        'stored_paths'
                            => $storedPaths,

                        'imports_deleted'
                            => $importsDeleted,

                        'participants_deleted'
                            => $participantsDeleted,

                        'assignments_deleted'
                            => $assignmentsDeleted,
                    ];
                },
                3
            );

        /*
         * Database sudah berhasil commit.
         *
         * File sumber tidak dihapus sebelum commit
         * agar kegagalan database tidak menyebabkan
         * file hilang sementara data kembali melalui rollback.
         */
        foreach (
            $result['stored_paths']
            as $storedPath
        ) {
            Storage::disk('local')
                ->delete(
                    $storedPath
                );
        }

        return [
            'imports_deleted'
                => (int) $result[
                    'imports_deleted'
                ],

            'participants_deleted'
                => (int) $result[
                    'participants_deleted'
                ],

            'assignments_deleted'
                => (int) $result[
                    'assignments_deleted'
                ],
        ];
    }

    private function assertPeriodCanBeDeleted(
        BpntPeriod $period
    ): void {
        if (
            (bool) $period->is_active
            &&
            (int) $period->active_slot === 1
        ) {
            throw ValidationException
                ::withMessages([
                    'period' => [
                        'BNBA pada periode aktif tidak dapat dihapus. Nonaktifkan periode terlebih dahulu.',
                    ],
                ]);
        }
    }

    private function recordAssignmentDeletion(
        SurveyorAssignment $assignment,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): void {
        $this->auditLogs
            ->record([
                'user_id'
                    => $actor->id,

                'action'
                    => 'surveyor_assignment.deleted_with_bnba',

                'auditable_type'
                    => SurveyorAssignment::class,

                'auditable_id'
                    => $assignment->id,

                'metadata' => [
                    'period_id'
                        => $assignment->period_id,

                    'period_name'
                        => $assignment
                            ->period
                            ->name,

                    'kelurahan_id'
                        => $assignment->kelurahan_id,

                    'kelurahan_name'
                        => $assignment
                            ->kelurahan
                            ->name,

                    'surveyor_id'
                        => $assignment->surveyor_id,

                    'surveyor_name'
                        => $assignment
                            ->surveyor
                            ->name,

                    'reason'
                        => 'bnba_deleted',
                ],

                'ip_address'
                    => $ipAddress,

                'user_agent'
                    => $userAgent,
            ]);
    }
}