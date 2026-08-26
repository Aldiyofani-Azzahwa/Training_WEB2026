<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ManagerTransactionMonitoringRepositoryInterface
{
    public function summary(int $periodId): array;

    public function wilayahBreakdown(int $periodId): array;

    public function eWarungBreakdown(int $periodId): array;

    public function surveyorBreakdown(int $periodId): array;

    public function paginateTransactions(
        int $periodId,
        array $filters,
        ?string $nikHash = null
    ): LengthAwarePaginator;
}