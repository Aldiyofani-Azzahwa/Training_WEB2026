<?php

declare(strict_types=1);

namespace App\Services\Assignment;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\BpntPeriodRepositoryInterface;
use App\Contracts\Repositories\SurveyorAssignmentRepositoryInterface;
use App\Contracts\Repositories\SurveyorRepositoryInterface;
use App\Models\BpntPeriod;
use App\Models\SurveyorAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SurveyorAssignmentService
{
    public const MAX_SURVEYORS_PER_KELURAHAN = 3;

    public function __construct(
        private readonly SurveyorAssignmentRepositoryInterface $assignments,
        private readonly BpntPeriodRepositoryInterface $periods,
        private readonly SurveyorRepositoryInterface $surveyors,
        private readonly AuditLogRepositoryInterface $auditLogs,
    ) {}

    public function listForActivePeriod(): array
    {
        $period =
            $this->requireActivePeriod();

        $assignments =
            $this->assignments
                ->forPeriod(
                    $period->id
                );

        $totalKelurahans =
            $this->assignments
                ->countKelurahansForPeriod(
                    $period->id
                );

        $assignedKelurahans =
            $assignments
                ->pluck(
                    'kelurahan_id'
                )
                ->unique()
                ->count();

        return [
            'period'
                => $period,

            'assignments'
                => $assignments,

            'total_kelurahans'
                => $totalKelurahans,

            'assigned_count'
                => $assignedKelurahans,

            'unassigned_count'
                => max(
                    0,
                    $totalKelurahans
                    -
                    $assignedKelurahans
                ),

            'total_assignments'
                => $assignments
                    ->count(),
        ];
    }

    public function assign(
        array $data,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): SurveyorAssignment {
        return DB::transaction(
            function () use (
                $data,
                $actor,
                $ipAddress,
                $userAgent
            ): SurveyorAssignment {
                $period =
                    $this->requireActivePeriod();

                /*
                 * Lock periode aktif agar request
                 * assignment pada periode yang sama
                 * berjalan berurutan.
                 *
                 * Ini membantu menjaga batas
                 * maksimal 3 Surveyor per kelurahan.
                 */
                $lockedPeriod =
                    BpntPeriod::query()
                        ->whereKey(
                            $period->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    ! (bool) $lockedPeriod
                        ->is_active
                    ||
                    (int) $lockedPeriod
                        ->active_slot !== 1
                ) {
                    throw ValidationException
                        ::withMessages([
                            'period' => [
                                'Periode aktif berubah. Muat ulang halaman lalu coba kembali.',
                            ],
                        ]);
                }

                $periodId =
                    (int) $lockedPeriod
                        ->id;

                $kelurahanId =
                    (int) $data[
                        'kelurahan_id'
                    ];

                $surveyorId =
                    (int) $data[
                        'surveyor_id'
                    ];

                /*
                 * Kelurahan wajib memiliki KPM
                 * pada periode aktif.
                 */
                if (
                    ! $this
                        ->assignments
                        ->periodHasKelurahan(
                            $periodId,
                            $kelurahanId
                        )
                ) {
                    throw ValidationException
                        ::withMessages([
                            'kelurahan_id' => [
                                'Kelurahan tidak memiliki KPM pada periode aktif.',
                            ],
                        ]);
                }

                $surveyor =
                    $this->surveyors
                        ->findOrFail(
                            $surveyorId
                        );

                if (
                    ! (bool) $surveyor
                        ->is_active
                ) {
                    throw ValidationException
                        ::withMessages([
                            'surveyor_id' => [
                                'Surveyor nonaktif tidak dapat menerima penugasan.',
                            ],
                        ]);
                }

                /*
                 * 1 Surveyor hanya boleh berada
                 * pada 1 kelurahan dalam periode
                 * aktif yang sama.
                 */
                $existingSurveyorAssignment =
                    $this->assignments
                        ->findForSurveyorInPeriod(
                            $periodId,
                            $surveyorId
                        );

                if (
                    $existingSurveyorAssignment
                    instanceof SurveyorAssignment
                ) {
                    throw ValidationException
                        ::withMessages([
                            'surveyor_id' => [
                                sprintf(
                                    'Surveyor sudah ditugaskan di Kelurahan %s pada periode aktif.',
                                    $existingSurveyorAssignment
                                        ->kelurahan
                                        ->name
                                ),
                            ],
                        ]);
                }

                /*
                 * Maksimum 3 Surveyor
                 * per kelurahan.
                 */
                $kelurahanAssignmentCount =
                    $this->assignments
                        ->countForKelurahan(
                            $periodId,
                            $kelurahanId
                        );

                if (
                    $kelurahanAssignmentCount
                    >=
                    self::MAX_SURVEYORS_PER_KELURAHAN
                ) {
                    throw ValidationException
                        ::withMessages([
                            'kelurahan_id' => [
                                'Kelurahan sudah memiliki maksimal 3 Surveyor pada periode aktif.',
                            ],
                        ]);
                }

                $saved =
                    $this->assignments
                        ->create(
                            $periodId,
                            $kelurahanId,
                            $surveyorId,
                            (int) $actor->id
                        );

                $this->auditLogs
                    ->record([
                        'user_id'
                            => $actor->id,

                        'action'
                            => 'surveyor_assignment.created',

                        'auditable_type'
                            => SurveyorAssignment::class,

                        'auditable_id'
                            => $saved->id,

                        'metadata' => [
                            'period_id'
                                => $periodId,

                            'period_name'
                                => $saved
                                    ->period
                                    ->name,

                            'kelurahan_id'
                                => $kelurahanId,

                            'kelurahan_name'
                                => $saved
                                    ->kelurahan
                                    ->name,

                            'surveyor_id'
                                => $saved
                                    ->surveyor_id,

                            'surveyor_name'
                                => $saved
                                    ->surveyor
                                    ->name,

                            'kelurahan_assignment_count'
                                => $kelurahanAssignmentCount
                                    + 1,

                            'max_surveyors_per_kelurahan'
                                => self::MAX_SURVEYORS_PER_KELURAHAN,
                        ],

                        'ip_address'
                            => $ipAddress,

                        'user_agent'
                            => $userAgent,
                    ]);

                return $saved;
            }
        );
    }

    public function unassign(
        int $assignmentId,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): void {
        DB::transaction(
            function () use (
                $assignmentId,
                $actor,
                $ipAddress,
                $userAgent
            ): void {
                $activePeriod =
                    $this->requireActivePeriod();

                $assignment =
                    $this->assignments
                        ->findOrFail(
                            $assignmentId
                        );

                /*
                 * Manager hanya boleh mengubah
                 * assignment periode aktif.
                 */
                if (
                    (int) $assignment
                        ->period_id
                    !==
                    (int) $activePeriod
                        ->id
                ) {
                    throw ValidationException
                        ::withMessages([
                            'assignment' => [
                                'Penugasan hanya dapat diubah pada periode yang sedang aktif.',
                            ],
                        ]);
                }

                $this->auditLogs
                    ->record([
                        'user_id'
                            => $actor->id,

                        'action'
                            => 'surveyor_assignment.deleted',

                        'auditable_type'
                            => SurveyorAssignment::class,

                        'auditable_id'
                            => $assignment->id,

                        'metadata' => [
                            'period_id'
                                => $assignment
                                    ->period_id,

                            'period_name'
                                => $assignment
                                    ->period
                                    ->name,

                            'kelurahan_id'
                                => $assignment
                                    ->kelurahan_id,

                            'kelurahan_name'
                                => $assignment
                                    ->kelurahan
                                    ->name,

                            'surveyor_id'
                                => $assignment
                                    ->surveyor_id,

                            'surveyor_name'
                                => $assignment
                                    ->surveyor
                                    ->name,
                        ],

                        'ip_address'
                            => $ipAddress,

                        'user_agent'
                            => $userAgent,
                    ]);

                $this->assignments
                    ->delete(
                        $assignment
                    );
            }
        );
    }

    /*
     * Dipakai saat Admin menghapus BNBA
     * dari periode NONAKTIF.
     *
     * Method ini tetap dipertahankan dari
     * bug fix sebelumnya.
     */
    public function clearForPeriod(
        int $periodId,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): int {
        return DB::transaction(
            function () use (
                $periodId,
                $actor,
                $ipAddress,
                $userAgent
            ): int {
                $period =
                    $this->periods
                        ->findOrFail(
                            $periodId
                        );

                if (
                    (bool) $period
                        ->is_active
                    &&
                    (int) $period
                        ->active_slot === 1
                ) {
                    throw ValidationException
                        ::withMessages([
                            'period' => [
                                'Penugasan Surveyor pada periode aktif tidak dapat dibersihkan melalui penghapusan BNBA.',
                            ],
                        ]);
                }

                $periodAssignments =
                    $this->assignments
                        ->forPeriod(
                            $periodId
                        );

                if (
                    $periodAssignments
                        ->isEmpty()
                ) {
                    return 0;
                }

                $deleted = 0;

                foreach (
                    $periodAssignments
                    as $assignment
                ) {
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
                                    => $assignment
                                        ->period_id,

                                'period_name'
                                    => $assignment
                                        ->period
                                        ->name,

                                'kelurahan_id'
                                    => $assignment
                                        ->kelurahan_id,

                                'kelurahan_name'
                                    => $assignment
                                        ->kelurahan
                                        ->name,

                                'surveyor_id'
                                    => $assignment
                                        ->surveyor_id,

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

                    $this->assignments
                        ->delete(
                            $assignment
                        );

                    $deleted++;
                }

                return $deleted;
            }
        );
    }

    private function requireActivePeriod(): BpntPeriod
    {
        $period =
            $this->periods
                ->active();

        if (
            ! $period
            instanceof BpntPeriod
        ) {
            throw ValidationException
                ::withMessages([
                    'period' => [
                        'Belum ada periode BPNT aktif. Hubungi Admin Dinsos.',
                    ],
                ]);
        }

        if (
            (int) $period
                ->participants_count
            <= 0
        ) {
            throw ValidationException
                ::withMessages([
                    'period' => [
                        'Periode aktif belum memiliki data KPM.',
                    ],
                ]);
        }

        return $period;
    }
}