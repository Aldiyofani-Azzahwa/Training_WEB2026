<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\SurveyorMonitoringReport;
use Illuminate\Support\Collection;

interface SurveyorMonitoringReportRepositoryInterface
{
    public function findForSurveyorInPeriod(
        int $periodId,
        int $surveyorId
    ): ?SurveyorMonitoringReport;

    public function saveConfiguration(
        int $periodId,
        int $assignmentId,
        int $surveyorId,
        int $kelurahanId,
        array $data
    ): SurveyorMonitoringReport;

    public function confirmedParticipants(
        int $periodId,
        int $kelurahanId
    ): Collection;
}