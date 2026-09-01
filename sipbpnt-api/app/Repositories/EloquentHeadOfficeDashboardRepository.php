<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\HeadOfficeDashboardRepositoryInterface;
use App\Enums\BnbaImportStatus;
use App\Enums\KpmVerificationStatus;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EloquentHeadOfficeDashboardRepository implements
    HeadOfficeDashboardRepositoryInterface
{
    public function summary(
        int $periodId,
        ?int $kecamatanId = null,
        ?int $kelurahanId = null
    ): array {
        $query = $this->participantQuery($periodId);

        $this->applyScope(
            $query,
            $kecamatanId,
            $kelurahanId
        );

        $row = $query
            ->selectRaw('COUNT(participants.id) as total_kpm')
            ->selectRaw(
                'COUNT(transactions.id) as transacted'
            )
            ->selectRaw(
                'COALESCE(
                    SUM(
                        CASE
                            WHEN transactions.id IS NOT NULL
                            THEN participants.entitlement_amount
                            ELSE 0
                        END
                    ),
                    0
                ) as amount_disbursed'
            )
            ->selectRaw(
                'MAX(transactions.transacted_at) as updated_at'
            )
            ->first();

        $totalKpm = (int) ($row?->total_kpm ?? 0);
        $transacted = (int) ($row?->transacted ?? 0);

        $verificationsQuery = DB::table('kpm_verifications')
            ->join('bpnt_participants', 'bpnt_participants.id', '=', 'kpm_verifications.bpnt_participant_id')
            ->join('kelurahans', 'kelurahans.id', '=', 'bpnt_participants.kelurahan_id')
            ->where('kpm_verifications.period_id', $periodId)
            ->where('kpm_verifications.active_slot', 1);

        if ($kelurahanId !== null) {
            $verificationsQuery->where('kelurahans.id', $kelurahanId);
        } elseif ($kecamatanId !== null) {
            $verificationsQuery->where('kelurahans.kecamatan_id', $kecamatanId);
        }

        $verificationCounts = $verificationsQuery
            ->select('kpm_verifications.status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('kpm_verifications.status')
            ->pluck('total', 'kpm_verifications.status');

        $deceased = (int) $verificationCounts->get(
            KpmVerificationStatus::DECEASED->value,
            0
        );

        $movedDomicile = (int) $verificationCounts->get(
            KpmVerificationStatus::MOVED_DOMICILE->value,
            0
        );

        $notClaimed = (int) $verificationCounts->get(
            KpmVerificationStatus::NOT_CLAIMED->value,
            0
        );

        $resolved = $transacted + $deceased + $movedDomicile + $notClaimed;

        return [
            'total_kpm' => $totalKpm,
            'transacted' => $transacted,
            'pending' => max(
                $totalKpm - $resolved,
                0
            ),
            'deceased' => $deceased,
            'moved_domicile' => $movedDomicile,
            'not_claimed' => $notClaimed,
            'not_transacted' => max(
                $totalKpm - $transacted,
                0
            ),
            'amount_disbursed' => (int) (
                $row?->amount_disbursed ?? 0
            ),
            'completion_percentage' => $totalKpm > 0
                ? round(
                    ($transacted / $totalKpm) * 100,
                    2
                )
                : 0.0,
            'updated_at' => $row?->updated_at,
        ];
    }

    public function regions(int $periodId): array
    {
        $rows = $this
            ->participantQuery($periodId)
            ->select([
                'kecamatans.id as kecamatan_id',
                'kecamatans.code as kecamatan_code',
                'kecamatans.name as kecamatan_name',
                'kelurahans.id as kelurahan_id',
                'kelurahans.code as kelurahan_code',
                'kelurahans.name as kelurahan_name',
            ])
            ->selectRaw(
                'COUNT(participants.id) as total_kpm'
            )
            ->selectRaw(
                'COUNT(transactions.id) as transacted'
            )
            ->selectRaw(
                'COALESCE(
                    SUM(
                        CASE
                            WHEN transactions.id IS NOT NULL
                            THEN participants.entitlement_amount
                            ELSE 0
                        END
                    ),
                    0
                ) as amount_disbursed'
            )
            ->groupBy([
                'kecamatans.id',
                'kecamatans.code',
                'kecamatans.name',
                'kelurahans.id',
                'kelurahans.code',
                'kelurahans.name',
            ])
            ->orderBy('kecamatans.name')
            ->orderBy('kelurahans.name')
            ->get();

        $verificationCounts = DB::table('kpm_verifications')
            ->where('period_id', $periodId)
            ->where('active_slot', 1)
            ->select('participant_kelurahan_id', 'status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('participant_kelurahan_id', 'status')
            ->get()
            ->groupBy('participant_kelurahan_id');

        $kelurahans = $rows
            ->map(function (object $row) use ($verificationCounts): array {
                $totalKpm = (int) $row->total_kpm;
                $transacted = (int) $row->transacted;
                $kelurahanId = (int) $row->kelurahan_id;
                $verifications = $verificationCounts->get($kelurahanId) ?? collect();

                $deceased = (int) $verifications->where('status', KpmVerificationStatus::DECEASED->value)->sum('total');
                $movedDomicile = (int) $verifications->where('status', KpmVerificationStatus::MOVED_DOMICILE->value)->sum('total');
                $notClaimed = (int) $verifications->where('status', KpmVerificationStatus::NOT_CLAIMED->value)->sum('total');

                $resolved = $transacted + $deceased + $movedDomicile + $notClaimed;

                return [
                    'kecamatan' => [
                        'id' => (int) $row->kecamatan_id,
                        'code' => (string) $row->kecamatan_code,
                        'name' => (string) $row->kecamatan_name,
                    ],
                    'kelurahan' => [
                        'id' => $kelurahanId,
                        'code' => (string) $row->kelurahan_code,
                        'name' => (string) $row->kelurahan_name,
                    ],
                    'total_kpm' => $totalKpm,
                    'transacted' => $transacted,
                    'pending' => max($totalKpm - $resolved, 0),
                    'deceased' => $deceased,
                    'moved_domicile' => $movedDomicile,
                    'not_claimed' => $notClaimed,
                    'not_transacted' => max(
                        $totalKpm - $transacted,
                        0
                    ),
                    'amount_disbursed' => (int) (
                        $row->amount_disbursed ?? 0
                    ),
                    'completion_percentage' => $totalKpm > 0
                        ? round(
                            ($transacted / $totalKpm) * 100,
                            2
                        )
                        : 0.0,
                ];
            })
            ->values();

        $kecamatans = $kelurahans
            ->groupBy('kecamatan.id')
            ->map(function ($items): array {
                $first = $items->first();

                $totalKpm = (int) $items->sum('total_kpm');
                $transacted = (int) $items->sum('transacted');
                $deceased = (int) $items->sum('deceased');
                $movedDomicile = (int) $items->sum('moved_domicile');
                $notClaimed = (int) $items->sum('not_claimed');
                $pending = (int) $items->sum('pending');

                return [
                    'kecamatan' => $first['kecamatan'],
                    'total_kpm' => $totalKpm,
                    'transacted' => $transacted,
                    'pending' => $pending,
                    'deceased' => $deceased,
                    'moved_domicile' => $movedDomicile,
                    'not_claimed' => $notClaimed,
                    'not_transacted' => max(
                        $totalKpm - $transacted,
                        0
                    ),
                    'amount_disbursed' => (int) $items->sum(
                        'amount_disbursed'
                    ),
                    'completion_percentage' => $totalKpm > 0
                        ? round(
                            ($transacted / $totalKpm) * 100,
                            2
                        )
                        : 0.0,
                ];
            })
            ->sortBy('kecamatan.name')
            ->values();

        return [
            'kecamatans' => $kecamatans->all(),
            'kelurahans' => $kelurahans->all(),
        ];
    }

    public function dailyTransactions(
        int $periodId,
        ?int $kecamatanId = null,
        ?int $kelurahanId = null
    ): array {
        $query = DB::table(
            'transactions'
        )
            ->join(
                'bpnt_participants as participants',
                'participants.id',
                '=',
                'transactions.bpnt_participant_id'
            )
            ->join(
                'bnba_imports as imports',
                'imports.id',
                '=',
                'participants.bnba_import_id'
            )
            ->join(
                'kelurahans',
                'kelurahans.id',
                '=',
                'participants.kelurahan_id'
            )
            ->join(
                'kecamatans',
                'kecamatans.id',
                '=',
                'kelurahans.kecamatan_id'
            )
            ->where(
                'transactions.period_id',
                $periodId
            )
            ->where(
                'imports.status',
                BnbaImportStatus::CONFIRMED->value
            );

        if ($kelurahanId !== null) {
            $query->where(
                'kelurahans.id',
                $kelurahanId
            );
        } elseif ($kecamatanId !== null) {
            $query->where(
                'kecamatans.id',
                $kecamatanId
            );
        }

        return $query
            ->selectRaw(
                'DATE(transactions.transacted_at) as transaction_date'
            )
            ->selectRaw(
                'COUNT(transactions.id) as total'
            )
            ->groupByRaw(
                'DATE(transactions.transacted_at)'
            )
            ->orderBy('transaction_date')
            ->pluck(
                'total',
                'transaction_date'
            )
            ->map(
                static fn ($total): int => (int) $total
            )
            ->all();
    }

    public function resolveScope(
        ?int $kecamatanId = null,
        ?int $kelurahanId = null
    ): array {
        $kelurahan = null;

        if ($kelurahanId !== null) {
            $kelurahan = DB::table('kelurahans')
                ->where('id', $kelurahanId)
                ->first([
                    'id',
                    'code',
                    'name',
                    'kecamatan_id',
                ]);

            if ($kelurahan === null) {
                throw ValidationException::withMessages([
                    'kelurahan_id' => [
                        'Kelurahan tidak ditemukan.',
                    ],
                ]);
            }

            if (
                $kecamatanId !== null
                && (int) $kelurahan->kecamatan_id
                    !== $kecamatanId
            ) {
                throw ValidationException::withMessages([
                    'kelurahan_id' => [
                        'Kelurahan tidak berada pada kecamatan yang dipilih.',
                    ],
                ]);
            }

            $kecamatanId = (int) $kelurahan->kecamatan_id;
        }

        $kecamatan = null;

        if ($kecamatanId !== null) {
            $kecamatan = DB::table('kecamatans')
                ->where('id', $kecamatanId)
                ->first([
                    'id',
                    'code',
                    'name',
                ]);

            if ($kecamatan === null) {
                throw ValidationException::withMessages([
                    'kecamatan_id' => [
                        'Kecamatan tidak ditemukan.',
                    ],
                ]);
            }
        }

        return [
            'level' => $kelurahan !== null
                ? 'kelurahan'
                : (
                    $kecamatan !== null
                        ? 'kecamatan'
                        : 'kota'
                ),
            'kecamatan' => $kecamatan !== null
                ? [
                    'id' => (int) $kecamatan->id,
                    'code' => (string) $kecamatan->code,
                    'name' => (string) $kecamatan->name,
                ]
                : null,
            'kelurahan' => $kelurahan !== null
                ? [
                    'id' => (int) $kelurahan->id,
                    'code' => (string) $kelurahan->code,
                    'name' => (string) $kelurahan->name,
                ]
                : null,
        ];
    }

    private function participantQuery(
        int $periodId
    ): Builder {
        return DB::table(
            'bpnt_participants as participants'
        )
            ->join(
                'bnba_imports as imports',
                'imports.id',
                '=',
                'participants.bnba_import_id'
            )
            ->join(
                'kelurahans',
                'kelurahans.id',
                '=',
                'participants.kelurahan_id'
            )
            ->join(
                'kecamatans',
                'kecamatans.id',
                '=',
                'kelurahans.kecamatan_id'
            )
            ->leftJoin(
                'transactions',
                function (JoinClause $join): void {
                    $join->on(
                        'transactions.bpnt_participant_id',
                        '=',
                        'participants.id'
                    )->on(
                        'transactions.period_id',
                        '=',
                        'participants.bpnt_period_id'
                    );
                }
            )
            ->where(
                'participants.bpnt_period_id',
                $periodId
            )
            ->where(
                'imports.status',
                BnbaImportStatus::CONFIRMED->value
            );
    }

    private function applyScope(
        Builder $query,
        ?int $kecamatanId,
        ?int $kelurahanId
    ): void {
        if ($kelurahanId !== null) {
            $query->where(
                'kelurahans.id',
                $kelurahanId
            );

            return;
        }

        if ($kecamatanId !== null) {
            $query->where(
                'kecamatans.id',
                $kecamatanId
            );
        }
    }
}