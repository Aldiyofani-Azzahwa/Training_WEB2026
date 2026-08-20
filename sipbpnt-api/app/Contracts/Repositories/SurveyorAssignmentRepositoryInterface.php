<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\SurveyorAssignment;
use Illuminate\Support\Collection;

interface SurveyorAssignmentRepositoryInterface
{
    public function forPeriod(
        int $periodId
    ): Collection;

    public function findOrFail(
        int $id
    ): SurveyorAssignment;

    public function findByScope(
        int $periodId,
        int $kelurahanId
    ): ?SurveyorAssignment;

    public function saveForScope(
        int $periodId,
        int $kelurahanId,
        int $surveyorId,
        int $assignedBy
    ): SurveyorAssignment;

    public function delete(
        SurveyorAssignment $assignment
    ): void;

    public function periodHasKelurahan(
        int $periodId,
        int $kelurahanId
    ): bool;

    public function countKelurahansForPeriod(
        int $periodId
    ): int;
}