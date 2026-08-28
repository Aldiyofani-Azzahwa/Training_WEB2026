<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\BpntReport;

interface BpntReportRepositoryInterface
{
    public function findByPeriod(
        int $periodId
    ): ?BpntReport;

    public function findSummaryByPeriod(
        int $periodId
    ): ?BpntReport;

    public function findByPeriodForUpdate(
        int $periodId
    ): ?BpntReport;

    public function isFinalForPeriod(
        int $periodId
    ): bool;

    public function createFinal(
        int $periodId,
        int $managerId,
        array $snapshot
    ): BpntReport;

    public function buildSnapshot(
        int $periodId
    ): array;

    public function buildSummary(
        int $periodId
    ): array;
}
