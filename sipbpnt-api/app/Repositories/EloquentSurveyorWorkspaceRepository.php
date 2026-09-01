<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\SurveyorWorkspaceRepositoryInterface;
use App\Enums\BnbaImportStatus;
use App\Models\BpntParticipant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentSurveyorWorkspaceRepository
    implements SurveyorWorkspaceRepositoryInterface
{
    public function countConfirmedParticipants(
        int $periodId,
        int $kelurahanId
    ): int {
        return BpntParticipant::query()
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
                        BnbaImportStatus
                            ::CONFIRMED
                            ->value
                    )
            )
            ->count();
    }

    public function paginateConfirmedParticipants(
        int $periodId,
        int $kelurahanId,
        array $filters,
        ?string $nikHash = null
    ): LengthAwarePaginator {
        /*
         * Browse KPM tetap dibatasi:
         *
         * periode aktif
         * +
         * kelurahan assignment.
         */
        $query =
            BpntParticipant::query()
                ->with([
                    'period',
                    'kpm',
                    'kelurahan.kecamatan',
                    'import',
                    'activeVerification',
                ])
                ->withExists([
                    'transactions as has_transaction' =>
                        fn ($transactionQuery) =>
                            $transactionQuery->where(
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
                            BnbaImportStatus
                                ::CONFIRMED
                                ->value
                        )
                );

        $search =
            trim(
                (string) (
                    $filters['search']
                    ?? ''
                )
            );

        if ($search !== '') {
            /*
             * Broad name search hanya berlaku
             * karena query tetap dikunci pada
             * kelurahan assignment.
             */
            $query->whereHas(
                'kpm',
                function (
                    $kpmQuery
                ) use (
                    $search,
                    $nikHash
                ): void {
                    $kpmQuery
                        ->where(
                            'full_name',
                            'like',
                            '%'
                            . $search
                            . '%'
                        );

                    if ($nikHash !== null) {
                        $kpmQuery
                            ->orWhere(
                                'nik_hash',
                                $nikHash
                            );
                    }
                }
            );
        }

        $status = $filters['status'] ?? 'all';
        if ($status === 'belum') {
            $query->whereDoesntHave('transactions', fn($q) => $q->where('period_id', $periodId))
                  ->whereDoesntHave('activeVerification');
        } elseif ($status === 'sudah') {
            $query->where(function($q) use ($periodId) {
                $q->whereHas('transactions', fn($t) => $t->where('period_id', $periodId))
                  ->orWhereHas('activeVerification');
            });
        }

        return $query
            ->orderBy(
                'bpnt_participants.id'
            )
            ->paginate(
                (int) (
                    $filters['per_page']
                    ?? 20
                )
            );
    }

    public function findConfirmedParticipantByNikHash(
        int $periodId,
        string $nikHash
    ): ?BpntParticipant {
        /*
         * Exact NIK lookup tidak dibatasi
         * kelurahan karena KPM lintas wilayah
         * tetap diperbolehkan bertransaksi.
         */
        return BpntParticipant::query()
            ->with([
                'period',
                'kpm',
                'kelurahan.kecamatan',
                'import',
                'activeVerification',
            ])
            ->withExists([
                'transactions as has_transaction' =>
                    fn ($transactionQuery) =>
                        $transactionQuery->where(
                            'period_id',
                            $periodId
                        ),
            ])
            ->where(
                'bpnt_period_id',
                $periodId
            )
            ->whereHas(
                'import',
                fn ($query) =>
                    $query->where(
                        'status',
                        BnbaImportStatus
                            ::CONFIRMED
                            ->value
                    )
            )
            ->whereHas(
                'kpm',
                fn ($query) =>
                    $query->where(
                        'nik_hash',
                        $nikHash
                    )
            )
            ->first();
    }
}