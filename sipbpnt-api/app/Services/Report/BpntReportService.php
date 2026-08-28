<?php

declare(strict_types=1);

namespace App\Services\Report;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\BpntPeriodRepositoryInterface;
use App\Contracts\Repositories\BpntReportRepositoryInterface;
use App\Enums\BpntReportStatus;
use App\Enums\UserRole;
use App\Models\BpntPeriod;
use App\Models\BpntReport;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class BpntReportService
{
    public function __construct(
        private readonly BpntPeriodRepositoryInterface $periods,
        private readonly BpntReportRepositoryInterface $reports,
        private readonly AuditLogRepositoryInterface $auditLogs,
    ) {}

    public function index(
        User $actor
    ): Collection {
        return $this->periods
            ->all()
            ->filter(
                fn (BpntPeriod $period): bool =>
                    (int) $period->participants_count > 0
            )
            ->map(function (BpntPeriod $period) use ($actor): ?array {
                $report = $this->reports->findSummaryByPeriod(
                    (int) $period->id
                );

                if (
                    ! $actor->hasRole(UserRole::MANAGER)
                    && ! $report instanceof BpntReport
                ) {
                    return null;
                }

                $snapshot = $report instanceof BpntReport
                    ? [
                        'summary' => $report->summary,
                    ]
                    : [
                        'summary' => $this->reports->buildSummary(
                            (int) $period->id
                        ),
                    ];

                return $this->serialize(
                    $period,
                    $report,
                    $snapshot,
                    $actor,
                    false
                );
            })
            ->filter()
            ->values();
    }

    public function show(
        User $actor,
        int $periodId
    ): array {
        $period = $this->periods->findOrFail(
            $periodId
        );

        $report = $this->reports->findByPeriod(
            $periodId
        );

        if (
            ! $actor->hasRole(UserRole::MANAGER)
            && ! $report instanceof BpntReport
        ) {
            throw new AuthorizationException(
                'Laporan final belum tersedia untuk periode ini.'
            );
        }

        $snapshot = $report instanceof BpntReport
            ? $report->snapshot
            : $this->snapshotForPeriod($period);

        return $this->serialize(
            $period,
            $report,
            $snapshot,
            $actor,
            true
        );
    }

    public function finalize(
        User $manager,
        int $periodId,
        ?string $ipAddress,
        ?string $userAgent
    ): array {
        return DB::transaction(
            function () use (
                $manager,
                $periodId,
                $ipAddress,
                $userAgent
            ): array {
                $period = $this->periods->findForUpdate(
                    $periodId
                );

                $existing = $this->reports
                    ->findByPeriodForUpdate(
                        $periodId
                    );

                if ($existing instanceof BpntReport) {
                    $existing = $this->reports->findByPeriod(
                        $periodId
                    ) ?? $existing;

                    return $this->serialize(
                        $period,
                        $existing,
                        $existing->snapshot,
                        $manager,
                        true
                    );
                }

                $snapshot = $this->snapshotForPeriod(
                    $period
                );

                $summary = $snapshot['summary'];

                if ((int) $summary['total_kpm'] <= 0) {
                    throw ValidationException::withMessages([
                        'report' => [
                            'Laporan tidak dapat difinalkan karena periode belum memiliki KPM confirmed.',
                        ],
                    ]);
                }

                if ((int) $summary['pending'] > 0) {
                    throw ValidationException::withMessages([
                        'report' => [
                            'Laporan belum dapat difinalkan karena masih ada '
                                .$summary['pending']
                                .' KPM yang belum ditindaklanjuti.',
                        ],
                    ]);
                }

                $report = $this->reports->createFinal(
                    $periodId,
                    (int) $manager->id,
                    $snapshot
                );

                $this->auditLogs->record([
                    'user_id' => $manager->id,
                    'action' => 'bpnt.report.finalized',
                    'auditable_type' => BpntReport::class,
                    'auditable_id' => $report->id,
                    'metadata' => [
                        'period_id' => $periodId,
                        'period_name' => $period->name,
                        'summary' => $summary,
                    ],
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                ]);

                return $this->serialize(
                    $period,
                    $report,
                    $snapshot,
                    $manager,
                    true
                );
            },
            3
        );
    }

    public function finalSnapshot(
        int $periodId
    ): array {
        $report = $this->reports->findByPeriod(
            $periodId
        );

        if (! $report instanceof BpntReport) {
            throw ValidationException::withMessages([
                'report' => [
                    'Laporan belum difinalkan dan belum dapat diekspor.',
                ],
            ]);
        }

        return $report->snapshot;
    }

    private function snapshotForPeriod(
        BpntPeriod $period
    ): array {
        return [
            'period' => [
                'id' => (int) $period->id,
                'code' => (string) $period->code,
                'name' => (string) $period->name,
                'year' => (int) $period->year,
            ],
            ...$this->reports->buildSnapshot(
                (int) $period->id
            ),
        ];
    }

    private function serialize(
        BpntPeriod $period,
        ?BpntReport $report,
        array $snapshot,
        User $actor,
        bool $includeSnapshot
    ): array {
        $isFinal = $report instanceof BpntReport
            && $report->status === BpntReportStatus::FINAL;

        $pending = (int) ($snapshot['summary']['pending'] ?? 0);
        $totalKpm = (int) ($snapshot['summary']['total_kpm'] ?? 0);
        $canFinalize = $actor->hasRole(UserRole::MANAGER)
            && ! $isFinal
            && $totalKpm > 0
            && $pending === 0;

        $blockingReason = null;

        if (! $isFinal && $totalKpm <= 0) {
            $blockingReason = 'Periode belum memiliki KPM confirmed.';
        } elseif (! $isFinal && $pending > 0) {
            $blockingReason = 'Masih ada '
                .$pending
                .' KPM yang belum ditindaklanjuti.';
        }

        return [
            'id' => $report?->id,
            'period' => [
                'id' => (int) $period->id,
                'code' => (string) $period->code,
                'name' => (string) $period->name,
                'year' => (int) $period->year,
                'is_active' => (bool) $period->is_active,
            ],
            'status' => [
                'code' => $isFinal ? 'final' : 'draft',
                'label' => $isFinal ? 'Final' : 'Draft',
            ],
            'summary' => $snapshot['summary'],
            'can_finalize' => $canFinalize,
            'blocking_reason' => $blockingReason,
            'finalized_by' => $report?->finalizedBy
                ? [
                    'id' => (int) $report->finalizedBy->id,
                    'name' => (string) $report->finalizedBy->name,
                ]
                : null,
            'finalized_at' => $report?->finalized_at
                ?->timezone('Asia/Jakarta')
                ->toIso8601String(),
            'snapshot' => $includeSnapshot
                ? $snapshot
                : null,
        ];
    }
}