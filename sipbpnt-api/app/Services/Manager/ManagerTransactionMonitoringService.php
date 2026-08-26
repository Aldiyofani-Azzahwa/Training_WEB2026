<?php

declare(strict_types=1);

namespace App\Services\Manager;

use App\Contracts\Repositories\BpntPeriodRepositoryInterface;
use App\Contracts\Repositories\ManagerTransactionMonitoringRepositoryInterface;
use App\Models\BpntPeriod;
use App\Support\Security\SensitiveIdentity;

final class ManagerTransactionMonitoringService
{
    public function __construct(
        private readonly BpntPeriodRepositoryInterface $periods,
        private readonly ManagerTransactionMonitoringRepositoryInterface $monitoring,
        private readonly SensitiveIdentity $identity,
    ) {}

    public function index(array $filters): array
    {
        $period = $this->periods->active();

        if (! $period instanceof BpntPeriod) {
            return [
                'period' => null,
                'summary' => $this->emptySummary(),
                'breakdowns' => [
                    'kecamatans' => [],
                    'kelurahans' => [],
                    'e_warungs' => [],
                    'surveyors' => [],
                ],
                'transactions' => null,
            ];
        }

        $search = trim((string) ($filters['search'] ?? ''));
        $nikHash = null;

        if (preg_match('/^\d{16}$/', $search) === 1) {
            $nikHash = $this->identity->hash($search);
        }

        $wilayah = $this->monitoring->wilayahBreakdown(
            (int) $period->id
        );

        return [
            'period' => $period,
            'summary' => $this->monitoring->summary(
                (int) $period->id
            ),
            'breakdowns' => [
                'kecamatans' => $wilayah['kecamatans'],
                'kelurahans' => $wilayah['kelurahans'],
                'e_warungs' => $this->monitoring->eWarungBreakdown(
                    (int) $period->id
                ),
                'surveyors' => $this->monitoring->surveyorBreakdown(
                    (int) $period->id
                ),
            ],
            'transactions' => $this->monitoring->paginateTransactions(
                (int) $period->id,
                $filters,
                $nikHash
            ),
        ];
    }

    private function emptySummary(): array
    {
        return [
            'total_kpm' => 0,
            'transacted' => 0,
            'pending' => 0,
            'active_verifications' => 0,
            'deceased' => 0,
            'moved_domicile' => 0,
            'not_claimed' => 0,
            'outside_assignment' => 0,
            'completion_percentage' => 0.0,
        ];
    }
}