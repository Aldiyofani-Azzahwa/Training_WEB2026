<?php

declare(strict_types=1);

namespace App\Services\Assignment;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\BpntPeriodRepositoryInterface;
use App\Contracts\Repositories\SurveyorAssignmentRepositoryInterface;
use App\Contracts\Repositories\SurveyorRepositoryInterface;
use App\Models\SurveyorAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SurveyorAssignmentService
{
    public function __construct(
        private readonly SurveyorAssignmentRepositoryInterface $assignments,
        private readonly BpntPeriodRepositoryInterface $periods,
        private readonly SurveyorRepositoryInterface $surveyors,
        private readonly AuditLogRepositoryInterface $auditLogs,
    ) {}

    public function listForPeriod(
        int $periodId
    ): array {
        $period =
            $this->periods
                ->findOrFail(
                    $periodId
                );

        $assignments =
            $this->assignments
                ->forPeriod(
                    $periodId
                );

        $totalKelurahans =
            $this->assignments
                ->countKelurahansForPeriod(
                    $periodId
                );

        return [
            'period'
                => $period,

            'assignments'
                => $assignments,

            'total_kelurahans'
                => $totalKelurahans,

            'assigned_count'
                => $assignments
                    ->count(),

            'unassigned_count'
                => max(
                    0,
                    $totalKelurahans
                    -
                    $assignments
                        ->count()
                ),
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
                $periodId =
                    (int) $data[
                        'period_id'
                    ];

                $kelurahanId =
                    (int) $data[
                        'kelurahan_id'
                    ];

                $surveyorId =
                    (int) $data[
                        'surveyor_id'
                    ];

                $period =
                    $this->periods
                        ->findOrFail(
                            $periodId
                        );

                if (
                    (int) $period
                        ->participants_count
                    === 0
                ) {
                    throw ValidationException
                        ::withMessages([
                            'period_id' => [
                                'Periode belum memiliki data BNBA/KPM yang dapat ditugaskan.',
                            ],
                        ]);
                }

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
                                'Kelurahan tidak memiliki KPM pada periode yang dipilih.',
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

                $existing =
                    $this->assignments
                        ->findByScope(
                            $periodId,
                            $kelurahanId
                        );

                if (
                    $existing
                    instanceof SurveyorAssignment
                    &&
                    (int) $existing
                        ->surveyor_id
                    === $surveyorId
                ) {
                    return $existing;
                }

                $saved =
                    $this->assignments
                        ->saveForScope(
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
                            => $existing
                                ? 'surveyor_assignment.reassigned'
                                : 'surveyor_assignment.created',

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

                            'previous_surveyor_id'
                                => $existing
                                    ?->surveyor_id,

                            'surveyor_id'
                                => $saved
                                    ->surveyor_id,

                            'surveyor_name'
                                => $saved
                                    ->surveyor
                                    ->name,
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
                $assignment =
                    $this->assignments
                        ->findOrFail(
                            $assignmentId
                        );

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
}