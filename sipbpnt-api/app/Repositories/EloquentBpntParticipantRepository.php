<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\BpntParticipantRepositoryInterface;
use App\Enums\BnbaImportStatus;
use App\Models\BnbaImport;
use App\Models\BnbaImportRow;
use App\Models\BpntParticipant;
use App\Models\BpntPeriod;
use App\Models\Kpm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentBpntParticipantRepository
    implements BpntParticipantRepositoryInterface
{
    public function existingNikHashesForPeriod(
        int $periodId,
        array $nikHashes
    ): array {
        if ($nikHashes === []) {
            return [];
        }

        return BpntParticipant::query()
            ->join(
                'kpms',
                'kpms.id',
                '=',
                'bpnt_participants.kpm_id'
            )
            ->where(
                'bpnt_participants.bpnt_period_id',
                $periodId
            )
            ->whereIn(
                'kpms.nik_hash',
                array_values(
                    array_unique($nikHashes)
                )
            )
            ->pluck('kpms.nik_hash')
            ->all();
    }

    public function createFromImportRow(
        BpntPeriod $period,
        Kpm $kpm,
        BnbaImport $import,
        BnbaImportRow $row
    ): void {
        BpntParticipant::query()->create([
            'bpnt_period_id'
                => $period->id,

            'kpm_id'
                => $kpm->id,

            'bnba_import_id'
                => $import->id,

            'import_row_number'
                => $row->row_number,

            'membership_year'
                => $row->membership_year,

            'e_warung_name_source'
                => $row->e_warung_name,

            'source_status'
                => $row->source_status,

            'source_description'
                => $row->source_description,

            'monthly_statuses'
                => $row->monthly_statuses,

            'sk_status'
                => $row->sk_status,

            'sk_description'
                => $row->sk_description,

            'apbn_march_status'
                => $row->apbn_march_status,

            'welfare_rank'
                => $row->welfare_rank,

            'entitlement_amount'
                => $row->nominal ?? 0,
        ]);
    }
    public function deleteForPeriod(
    int $periodId
): int {
    return BpntParticipant::query()
        ->where(
            'bpnt_period_id',
            $periodId
        )
        ->delete();
}

    public function paginateConfirmed(
        array $filters,
        ?string $nikHash = null
    ): LengthAwarePaginator {
        $query = BpntParticipant::query()
            ->with([
                'period',
                'kpm',
                'import',
            ])
            ->where(
                'bpnt_period_id',
                (int) $filters['period_id']
            )
            ->whereHas(
                'import',
                function ($query): void {
                    $query->where(
                        'status',
                        BnbaImportStatus::CONFIRMED->value
                    );
                }
            );

        $search = trim(
            (string) ($filters['search'] ?? '')
        );

        if ($search !== '') {
            $query->where(
                function ($query) use (
                    $search,
                    $nikHash
                ): void {
                    $query
                        ->whereHas(
                            'kpm',
                            function ($kpmQuery) use (
                                $search,
                                $nikHash
                            ): void {
                                $kpmQuery
                                    ->where(
                                        'full_name',
                                        'like',
                                        '%'.$search.'%'
                                    )
                                    ->orWhere(
                                        'kelurahan',
                                        'like',
                                        '%'.$search.'%'
                                    )
                                    ->orWhere(
                                        'kecamatan',
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
                        )
                        ->orWhere(
                            'e_warung_name_source',
                            'like',
                            '%'.$search.'%'
                        );
                }
            );
        }

        $kecamatan = trim(
            (string) ($filters['kecamatan'] ?? '')
        );

        if ($kecamatan !== '') {
            $query->whereHas(
                'kpm',
                fn ($kpmQuery) =>
                    $kpmQuery->where(
                        'kecamatan',
                        $kecamatan
                    )
            );
        }

        $kelurahan = trim(
            (string) ($filters['kelurahan'] ?? '')
        );

        if ($kelurahan !== '') {
            $query->whereHas(
                'kpm',
                fn ($kpmQuery) =>
                    $kpmQuery->where(
                        'kelurahan',
                        $kelurahan
                    )
            );
        }

        $eWarung = trim(
            (string) ($filters['e_warung'] ?? '')
        );

        if ($eWarung !== '') {
            $query->where(
                'e_warung_name_source',
                $eWarung
            );
        }

        return $query
            ->orderBy('id')
            ->paginate(
                (int) (
                    $filters['per_page']
                    ?? 25
                )
            );
    }

    public function filterOptions(
        int $periodId
    ): array {
        $baseQuery = BpntParticipant::query()
            ->where(
                'bpnt_period_id',
                $periodId
            )
            ->whereHas(
                'import',
                fn ($query) =>
                    $query->where(
                        'status',
                        BnbaImportStatus::CONFIRMED->value
                    )
            );

        $kecamatan = (clone $baseQuery)
            ->join(
                'kpms',
                'kpms.id',
                '=',
                'bpnt_participants.kpm_id'
            )
            ->whereNotNull('kpms.kecamatan')
            ->where(
                'kpms.kecamatan',
                '<>',
                ''
            )
            ->distinct()
            ->orderBy('kpms.kecamatan')
            ->pluck('kpms.kecamatan')
            ->values()
            ->all();

        $kelurahan = (clone $baseQuery)
            ->join(
                'kpms',
                'kpms.id',
                '=',
                'bpnt_participants.kpm_id'
            )
            ->whereNotNull('kpms.kelurahan')
            ->where(
                'kpms.kelurahan',
                '<>',
                ''
            )
            ->distinct()
            ->orderBy('kpms.kelurahan')
            ->pluck('kpms.kelurahan')
            ->values()
            ->all();

        $eWarungs = (clone $baseQuery)
            ->whereNotNull(
                'e_warung_name_source'
            )
            ->where(
                'e_warung_name_source',
                '<>',
                ''
            )
            ->distinct()
            ->orderBy(
                'e_warung_name_source'
            )
            ->pluck(
                'e_warung_name_source'
            )
            ->values()
            ->all();

        return [
            'kecamatan' => $kecamatan,
            'kelurahan' => $kelurahan,
            'e_warungs' => $eWarungs,
        ];
    }
}