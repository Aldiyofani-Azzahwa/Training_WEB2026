<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\ManagerTransactionMonitoringRepositoryInterface;
use App\Enums\BnbaImportStatus;
use App\Models\BpntParticipant;
use App\Models\BpntTransaction;
use App\Models\EWarung;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentManagerTransactionMonitoringRepository
    implements ManagerTransactionMonitoringRepositoryInterface
{
    private const TRANSACTION_RELATIONS = [
        'participant.kpm',
        'participant.kelurahan.kecamatan',
        'eWarung',
        'surveyor',
        'participantKelurahan.kecamatan',
        'surveyorKelurahan.kecamatan',
    ];

    public function summary(int $periodId): array
    {
        $totalKpm = $this
            ->confirmedParticipantQuery($periodId)
            ->count();

        $transactionCount = BpntTransaction::query()
            ->where('period_id', $periodId)
            ->count();

        $outsideAssignmentCount = BpntTransaction::query()
            ->where('period_id', $periodId)
            ->whereColumn(
                'participant_kelurahan_id',
                '!=',
                'surveyor_kelurahan_id'
            )
            ->count();

        $verificationCounts = DB::table('kpm_verifications')
            ->where('period_id', $periodId)
            ->where('active_slot', 1)
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $deceased = (int) $verificationCounts->get(
            'deceased',
            0
        );

        $movedDomicile = (int) $verificationCounts->get(
            'moved_domicile',
            0
        );

        $notClaimed = (int) $verificationCounts->get(
            'not_claimed',
            0
        );

        $verificationTotal =
            $deceased
            + $movedDomicile
            + $notClaimed;

        $resolved =
            $transactionCount
            + $verificationTotal;

        $pending = max(
            $totalKpm - $resolved,
            0
        );

        return [
            'total_kpm' => $totalKpm,
            'transacted' => $transactionCount,
            'pending' => $pending,
            'active_verifications' => $verificationTotal,
            'deceased' => $deceased,
            'moved_domicile' => $movedDomicile,
            'not_claimed' => $notClaimed,
            'outside_assignment' => $outsideAssignmentCount,
            'completion_percentage' => $totalKpm > 0
                ? round(($resolved / $totalKpm) * 100, 2)
                : 0.0,
        ];
    }

    public function wilayahBreakdown(int $periodId): array
    {
        $participantRows = $this
            ->confirmedParticipantQuery($periodId)
            ->select('kelurahan_id')
            ->selectRaw('COUNT(*) as total_kpm')
            ->groupBy('kelurahan_id')
            ->with('kelurahan.kecamatan')
            ->get();

        $transactionCounts = DB::table('transactions')
            ->where('period_id', $periodId)
            ->select('participant_kelurahan_id')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('participant_kelurahan_id')
            ->pluck('total', 'participant_kelurahan_id');

        $verificationCounts = DB::table('kpm_verifications')
            ->where('period_id', $periodId)
            ->where('active_slot', 1)
            ->select('participant_kelurahan_id')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('participant_kelurahan_id')
            ->pluck('total', 'participant_kelurahan_id');

        $kelurahans = $participantRows
            ->map(function (BpntParticipant $row) use (
                $transactionCounts,
                $verificationCounts
            ): array {
                $kelurahan = $row->kelurahan;
                $kecamatan = $kelurahan?->kecamatan;
                $kelurahanId = (int) $row->kelurahan_id;
                $totalKpm = (int) $row->getAttribute('total_kpm');

                $transacted = (int) $transactionCounts->get(
                    $kelurahanId,
                    0
                );

                $verified = (int) $verificationCounts->get(
                    $kelurahanId,
                    0
                );

                return [
                    'kecamatan' => [
                        'id' => $kecamatan
                            ? (int) $kecamatan->id
                            : null,
                        'name' => $kecamatan?->name,
                    ],
                    'kelurahan' => [
                        'id' => $kelurahan
                            ? (int) $kelurahan->id
                            : null,
                        'name' => $kelurahan?->name,
                    ],
                    'total_kpm' => $totalKpm,
                    'transacted' => $transacted,
                    'active_verifications' => $verified,
                    'pending' => max(
                        $totalKpm - $transacted - $verified,
                        0
                    ),
                ];
            })
            ->sortBy([
                ['kecamatan.name', 'asc'],
                ['kelurahan.name', 'asc'],
            ])
            ->values();

        $kecamatans = $kelurahans
            ->groupBy('kecamatan.id')
            ->map(function (Collection $rows): array {
                $first = $rows->first();

                return [
                    'kecamatan' => $first['kecamatan'],
                    'total_kpm' => (int) $rows->sum('total_kpm'),
                    'transacted' => (int) $rows->sum('transacted'),
                    'active_verifications' => (int) $rows->sum(
                        'active_verifications'
                    ),
                    'pending' => (int) $rows->sum('pending'),
                ];
            })
            ->sortBy('kecamatan.name')
            ->values();

        return [
            'kecamatans' => $kecamatans->all(),
            'kelurahans' => $kelurahans->all(),
        ];
    }

    public function eWarungBreakdown(int $periodId): array
    {
        $transactionCounts = DB::table('transactions')
            ->where('period_id', $periodId)
            ->select('e_warung_id')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('e_warung_id')
            ->pluck('total', 'e_warung_id');

        return EWarung::query()
            ->orderBy('name')
            ->get()
            ->map(function (EWarung $eWarung) use (
                $transactionCounts
            ): array {
                return [
                    'id' => (int) $eWarung->id,
                    'name' => (string) $eWarung->name,
                    'is_active' => (bool) $eWarung->is_active,
                    'transactions' => (int) $transactionCounts->get(
                        $eWarung->id,
                        0
                    ),
                ];
            })
            ->filter(
                fn (array $row): bool =>
                    $row['is_active']
                    || $row['transactions'] > 0
            )
            ->sort(function (array $left, array $right): int {
                $countComparison = $right['transactions']
                    <=> $left['transactions'];

                if ($countComparison !== 0) {
                    return $countComparison;
                }

                return strcasecmp(
                    $left['name'],
                    $right['name']
                );
            })
            ->values()
            ->all();
    }

    public function surveyorBreakdown(int $periodId): array
    {
        $transactionRows = DB::table('transactions')
            ->where('period_id', $periodId)
            ->select('surveyor_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                'SUM(CASE WHEN participant_kelurahan_id != surveyor_kelurahan_id THEN 1 ELSE 0 END) as outside_total'
            )
            ->groupBy('surveyor_id')
            ->get()
            ->keyBy('surveyor_id');

        return DB::table('surveyor_assignments')
            ->join(
                'users',
                'users.id',
                '=',
                'surveyor_assignments.surveyor_id'
            )
            ->join(
                'kelurahans',
                'kelurahans.id',
                '=',
                'surveyor_assignments.kelurahan_id'
            )
            ->join(
                'kecamatans',
                'kecamatans.id',
                '=',
                'kelurahans.kecamatan_id'
            )
            ->where('surveyor_assignments.period_id', $periodId)
            ->orderBy('users.name')
            ->select([
                'users.id',
                'users.name',
                'users.username',
                'kelurahans.id as kelurahan_id',
                'kelurahans.name as kelurahan_name',
                'kecamatans.id as kecamatan_id',
                'kecamatans.name as kecamatan_name',
            ])
            ->get()
            ->map(function (object $row) use (
                $transactionRows
            ): array {
                $transaction = $transactionRows->get($row->id);

                return [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'username' => (string) $row->username,
                    'assignment' => [
                        'kecamatan' => [
                            'id' => (int) $row->kecamatan_id,
                            'name' => (string) $row->kecamatan_name,
                        ],
                        'kelurahan' => [
                            'id' => (int) $row->kelurahan_id,
                            'name' => (string) $row->kelurahan_name,
                        ],
                    ],
                    'transactions' => $transaction
                        ? (int) $transaction->total
                        : 0,
                    'outside_assignment' => $transaction
                        ? (int) $transaction->outside_total
                        : 0,
                ];
            })
            ->sort(function (array $left, array $right): int {
                $countComparison = $right['transactions']
                    <=> $left['transactions'];

                if ($countComparison !== 0) {
                    return $countComparison;
                }

                return strcasecmp(
                    $left['name'],
                    $right['name']
                );
            })
            ->values()
            ->all();
    }

    public function paginateTransactions(
        int $periodId,
        array $filters,
        ?string $nikHash = null
    ): LengthAwarePaginator {
        $query = BpntTransaction::query()
            ->with(self::TRANSACTION_RELATIONS)
            ->where('period_id', $periodId);

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->whereHas(
                'participant.kpm',
                function (Builder $kpmQuery) use (
                    $search,
                    $nikHash
                ): void {
                    $kpmQuery->where(
                        'full_name',
                        'like',
                        '%'.$search.'%'
                    );

                    if ($nikHash !== null) {
                        $kpmQuery->orWhere(
                            'nik_hash',
                            $nikHash
                        );
                    }
                }
            );
        }

        if (isset($filters['kecamatan_id'])) {
            $query->whereHas(
                'participantKelurahan',
                fn (Builder $kelurahanQuery) =>
                    $kelurahanQuery->where(
                        'kecamatan_id',
                        (int) $filters['kecamatan_id']
                    )
            );
        }

        if (isset($filters['kelurahan_id'])) {
            $query->where(
                'participant_kelurahan_id',
                (int) $filters['kelurahan_id']
            );
        }

        if (isset($filters['e_warung_id'])) {
            $query->where(
                'e_warung_id',
                (int) $filters['e_warung_id']
            );
        }

        if (isset($filters['surveyor_id'])) {
            $query->where(
                'surveyor_id',
                (int) $filters['surveyor_id']
            );
        }

        if (array_key_exists('outside_assignment', $filters)) {
            $operator = (bool) $filters['outside_assignment']
                ? '!='
                : '=';

            $query->whereColumn(
                'participant_kelurahan_id',
                $operator,
                'surveyor_kelurahan_id'
            );
        }

        return $query
            ->orderByDesc('transacted_at')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 20));
    }

    private function confirmedParticipantQuery(int $periodId): Builder
    {
        return BpntParticipant::query()
            ->where('bpnt_period_id', $periodId)
            ->whereHas(
                'import',
                fn (Builder $query) =>
                    $query->where(
                        'status',
                        BnbaImportStatus::CONFIRMED->value
                    )
            );
    }
}