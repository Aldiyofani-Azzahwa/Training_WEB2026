<?php

declare(strict_types=1);

namespace App\Services\HeadOffice;

use App\Contracts\Repositories\BpntPeriodRepositoryInterface;
use App\Contracts\Repositories\HeadOfficeDashboardRepositoryInterface;
use App\Models\BpntPeriod;
use Carbon\CarbonImmutable;
use DateTimeInterface;

final class HeadOfficeDashboardService
{
    private const MONTHS = [
        'januari' => 1,
        'februari' => 2,
        'maret' => 3,
        'april' => 4,
        'mei' => 5,
        'juni' => 6,
        'juli' => 7,
        'agustus' => 8,
        'september' => 9,
        'oktober' => 10,
        'november' => 11,
        'desember' => 12,
    ];

    private const SHORT_MONTHS = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'Mei',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Agu',
        9 => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des',
    ];

    public function __construct(
        private readonly BpntPeriodRepositoryInterface $periods,
        private readonly HeadOfficeDashboardRepositoryInterface $dashboard,
    ) {}

    public function show(array $filters): array
    {
        $period = $this->periods->active();

        if (! $period instanceof BpntPeriod) {
            return $this->emptyDashboard();
        }

        $kecamatanId = isset($filters['kecamatan_id'])
            ? (int) $filters['kecamatan_id']
            : null;

        $kelurahanId = isset($filters['kelurahan_id'])
            ? (int) $filters['kelurahan_id']
            : null;

        $scope = $this->dashboard->resolveScope(
            $kecamatanId,
            $kelurahanId
        );

        $summary = $this->dashboard->summary(
            (int) $period->id,
            $kecamatanId,
            $kelurahanId
        );

        $updatedAt = $summary['updated_at'] ?? null;
        unset($summary['updated_at']);

        $dailyTransactions =
            $this->dashboard->dailyTransactions(
                (int) $period->id,
                $kecamatanId,
                $kelurahanId
            );

        return [
            'period' => [
                'id' => (int) $period->id,
                'code' => (string) $period->code,
                'name' => (string) $period->name,
                'year' => (int) $period->year,
            ],
            'scope' => $scope,
            'summary' => $summary,
            'trend' => $this->buildTrend(
                $period,
                $dailyTransactions
            ),
            'regions' => $this->dashboard->regions(
                (int) $period->id
            ),
            'updated_at' => $this->serializeUpdatedAt(
                $updatedAt,
                $period
            ),
        ];
    }

    private function buildTrend(
        BpntPeriod $period,
        array $dailyTransactions
    ): array {
        $start = $this->resolveMonthStart(
            $period,
            $dailyTransactions
        );

        $end = $start->endOfMonth();
        $cursor = $start;
        $cumulative = 0;
        $result = [];

        while ($cursor->lessThanOrEqualTo($end)) {
            $date = $cursor->toDateString();
            $daily = (int) (
                $dailyTransactions[$date] ?? 0
            );

            $cumulative += $daily;

            $result[] = [
                'date' => $date,
                'label' => $cursor->day
                    .' '
                    .self::SHORT_MONTHS[$cursor->month],
                'daily' => $daily,
                'cumulative' => $cumulative,
            ];

            $cursor = $cursor->addDay();
        }

        return $result;
    }

    private function resolveMonthStart(
        BpntPeriod $period,
        array $dailyTransactions
    ): CarbonImmutable {
        $periodLabel = mb_strtolower(
            (string) $period->name
            .' '
            .(string) $period->code
        );

        foreach (self::MONTHS as $name => $number) {
            if (str_contains($periodLabel, $name)) {
                return CarbonImmutable::create(
                    (int) $period->year,
                    $number,
                    1,
                    0,
                    0,
                    0,
                    'Asia/Jakarta'
                );
            }
        }

        $dates = array_keys($dailyTransactions);
        sort($dates);

        if ($dates !== []) {
            return CarbonImmutable::parse(
                $dates[0],
                'Asia/Jakarta'
            )->startOfMonth();
        }

        $fallbackMonth = $period->created_at
            ? (int) $period->created_at->month
            : 1;

        return CarbonImmutable::create(
            (int) $period->year,
            $fallbackMonth,
            1,
            0,
            0,
            0,
            'Asia/Jakarta'
        );
    }

    private function serializeUpdatedAt(
        mixed $updatedAt,
        BpntPeriod $period
    ): ?string {
        if ($updatedAt instanceof DateTimeInterface) {
            return CarbonImmutable::instance(
                $updatedAt
            )
                ->timezone('Asia/Jakarta')
                ->toIso8601String();
        }

        if (
            is_string($updatedAt)
            && trim($updatedAt) !== ''
        ) {
            return CarbonImmutable::parse(
                $updatedAt,
                'Asia/Jakarta'
            )->toIso8601String();
        }

        return $period->updated_at
            ?->copy()
            ->timezone('Asia/Jakarta')
            ->toIso8601String();
    }

    private function emptyDashboard(): array
    {
        return [
            'period' => null,
            'scope' => [
                'level' => 'kota',
                'kecamatan' => null,
                'kelurahan' => null,
            ],
            'summary' => [
                'total_kpm' => 0,
                'transacted' => 0,
                'pending' => 0,
                'deceased' => 0,
                'moved_domicile' => 0,
                'not_claimed' => 0,
                'not_transacted' => 0,
                'amount_disbursed' => 0,
                'completion_percentage' => 0.0,
            ],
            'trend' => [],
            'regions' => [
                'kecamatans' => [],
                'kelurahans' => [],
            ],
            'updated_at' => null,
        ];
    }
}