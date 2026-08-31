<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\SurveyorMonitoringReportRepositoryInterface;
use App\Enums\BnbaImportStatus;
use App\Models\BpntParticipant;
use App\Models\SurveyorMonitoringReport;
use Illuminate\Support\Collection;
use RuntimeException;

final class EloquentSurveyorMonitoringReportRepository
    implements SurveyorMonitoringReportRepositoryInterface
{
    public function findForSurveyorInPeriod(
        int $periodId,
        int $surveyorId
    ): ?SurveyorMonitoringReport {
        return SurveyorMonitoringReport::query()
            ->with([
                'period',
                'assignment.kelurahan.kecamatan',
                'surveyor',
                'kelurahan.kecamatan',
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

    public function saveConfiguration(
        int $periodId,
        int $assignmentId,
        int $surveyorId,
        int $kelurahanId,
        array $data
    ): SurveyorMonitoringReport {
        SurveyorMonitoringReport::query()
            ->updateOrCreate(
                [
                    'period_id' => $periodId,
                    'surveyor_id' => $surveyorId,
                ],
                [
                    'assignment_id' => $assignmentId,
                    'kelurahan_id' => $kelurahanId,

                    'commodities' => array_values(
                        $data['commodities']
                    ),

                    'social_officer_name' =>
                        $data['social_officer_name'],

                    'distribution_assistant_name' =>
                        $data['distribution_assistant_name'],
                ]
            );

        $report = $this->findForSurveyorInPeriod(
            $periodId,
            $surveyorId
        );

        if (! $report instanceof SurveyorMonitoringReport) {
            throw new RuntimeException(
                'Pengaturan laporan monitoring gagal disimpan.'
            );
        }

        return $report;
    }

    public function confirmedParticipants(
        int $periodId,
        int $kelurahanId
    ): Collection {
        return BpntParticipant::query()
            ->with([
                'kpm',
                'kelurahan.kecamatan',

                'transactions' => fn ($query) =>
                    $query
                        ->where(
                            'period_id',
                            $periodId
                        )
                        ->with('eWarung'),

                'activeVerification' => fn ($query) =>
                    $query->where(
                        'period_id',
                        $periodId
                    ),
            ])
            ->where(
                'bpnt_period_id',
                $periodId
            )
            ->where(
                'kelurahan_id',
                $kelurahanId
            )
            ->whereHas(
                'import',
                fn ($query) =>
                    $query->where(
                        'status',
                        BnbaImportStatus::CONFIRMED->value
                    )
            )
            ->orderBy('import_row_number')
            ->orderBy('id')
            ->get();
    }
}