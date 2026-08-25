<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\SurveyorAssignmentRepositoryInterface;
use App\Models\BpntParticipant;
use App\Models\SurveyorAssignment;
use Illuminate\Support\Collection;

final class EloquentSurveyorAssignmentRepository
    implements SurveyorAssignmentRepositoryInterface
{
    public function forPeriod(
        int $periodId
    ): Collection {
        return SurveyorAssignment::query()
            ->with([
                'period',
                'surveyor',
                'kelurahan.kecamatan',
                'assignedBy',
            ])
            ->where(
                'period_id',
                $periodId
            )
            ->orderBy(
                'kelurahan_id'
            )
            ->orderBy(
                'id'
            )
            ->get();
    }

    public function findOrFail(
        int $id
    ): SurveyorAssignment {
        return SurveyorAssignment::query()
            ->with([
                'period',
                'surveyor',
                'kelurahan.kecamatan',
                'assignedBy',
            ])
            ->findOrFail(
                $id
            );
    }

    public function create(
        int $periodId,
        int $kelurahanId,
        int $surveyorId,
        int $assignedBy
    ): SurveyorAssignment {
        $assignment =
            SurveyorAssignment::query()
                ->create([
                    'period_id'
                        => $periodId,

                    'kelurahan_id'
                        => $kelurahanId,

                    'surveyor_id'
                        => $surveyorId,

                    'assigned_by'
                        => $assignedBy,

                    'assigned_at'
                        => now(),
                ]);

        return $this->findOrFail(
            $assignment->id
        );
    }

    public function findForSurveyorInPeriod(
        int $periodId,
        int $surveyorId
    ): ?SurveyorAssignment {
        return SurveyorAssignment::query()
            ->with([
                'period',
                'surveyor',
                'kelurahan.kecamatan',
                'assignedBy',
            ])
            ->where(
                'period_id',
                $periodId
            )
            ->where(
                'surveyor_id',
                $surveyorId
            )
            ->first();
    }

    public function countForKelurahan(
        int $periodId,
        int $kelurahanId
    ): int {
        return SurveyorAssignment::query()
            ->where(
                'period_id',
                $periodId
            )
            ->where(
                'kelurahan_id',
                $kelurahanId
            )
            ->count();
    }

    public function delete(
        SurveyorAssignment $assignment
    ): void {
        $assignment->delete();
    }

    public function periodHasKelurahan(
        int $periodId,
        int $kelurahanId
    ): bool {
        return BpntParticipant::query()
            ->where(
                'bpnt_period_id',
                $periodId
            )
            ->where(
                'kelurahan_id',
                $kelurahanId
            )
            ->exists();
    }

    public function countKelurahansForPeriod(
        int $periodId
    ): int {
        return BpntParticipant::query()
            ->where(
                'bpnt_period_id',
                $periodId
            )
            ->whereNotNull(
                'kelurahan_id'
            )
            ->distinct()
            ->count(
                'kelurahan_id'
            );
    }
}