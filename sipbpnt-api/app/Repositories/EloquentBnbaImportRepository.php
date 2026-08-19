<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\BnbaImportRepositoryInterface;
use App\Enums\BnbaRowStatus;
use App\Models\BnbaImport;
use App\Models\BnbaImportRow;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class EloquentBnbaImportRepository
    implements BnbaImportRepositoryInterface
{
    public function create(
        array $data
    ): BnbaImport {
        return BnbaImport::query()
            ->create($data);
    }

    public function insertRows(
        array $rows
    ): void {
        foreach (
            array_chunk(
                $rows,
                250
            ) as $chunk
        ) {
            BnbaImportRow::query()
                ->insert($chunk);
        }
    }

    public function findOrFail(
        int $id
    ): BnbaImport {
        return BnbaImport::query()
            ->with([
                'period',
                'uploader:id,name,username',
            ])
            ->findOrFail($id);
    }

    public function findForUpdate(
        int $id
    ): BnbaImport {
        return BnbaImport::query()
            ->whereKey($id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function update(
        BnbaImport $import,
        array $data
    ): BnbaImport {
        $import
            ->forceFill($data)
            ->save();

        return $import->refresh();
    }

    public function paginate(
        int $perPage = 15
    ): LengthAwarePaginator {
        return BnbaImport::query()
            ->with([
                'period',
                'uploader:id,name,username',
            ])
            ->latest('id')
            ->paginate($perPage);
    }

    public function paginateRows(
        BnbaImport $import,
        ?string $status,
        ?string $search,
        ?string $nikHash,
        int $perPage = 50
    ): LengthAwarePaginator {
        $query =
            BnbaImportRow::query()
                ->where(
                    'bnba_import_id',
                    $import->id
                )
                ->orderBy(
                    'row_number'
                );

        if ($status !== null) {
            $query->where(
                'status',
                $status
            );
        }

        if (
            $search !== null
            && $search !== ''
        ) {
            $query->where(
                function ($builder) use (
                    $search,
                    $nikHash
                ): void {
                    $builder
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
                        )
                        ->orWhere(
                            'e_warung_name',
                            'like',
                            '%'.$search.'%'
                        );

                    if ($nikHash !== null) {
                        $builder
                            ->orWhere(
                                'nik_hash',
                                $nikHash
                            );
                    }
                }
            );
        }

        return $query->paginate(
            $perPage
        );
    }

    public function confirmableRows(
        BnbaImport $import
    ): Collection {
        return BnbaImportRow::query()
            ->where(
                'bnba_import_id',
                $import->id
            )
            ->whereIn(
                'status',
                [
                    BnbaRowStatus
                        ::VALID
                        ->value,

                    BnbaRowStatus
                        ::WARNING
                        ->value,
                ]
            )
            ->orderBy(
                'row_number'
            )
            ->get();
    }

    public function latestForPeriod(
        int $periodId
    ): ?BnbaImport {
        return BnbaImport::query()
            ->where(
                'bpnt_period_id',
                $periodId
            )
            ->latest('id')
            ->first();
    }

    public function forPeriodForUpdate(
        int $periodId
    ): Collection {
        return BnbaImport::query()
            ->where(
                'bpnt_period_id',
                $periodId
            )
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    public function deleteForPeriod(
        int $periodId
    ): int {
        return BnbaImport::query()
            ->where(
                'bpnt_period_id',
                $periodId
            )
            ->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Retention Audit
    |--------------------------------------------------------------------------
    |
    */

    public function latestForRetentionAudit(
        int $limit
    ): Collection {
        $safeLimit =
            min(
                max(
                    $limit,
                    1
                ),
                1000
            );

        return BnbaImport::query()
            ->with([
                'period:id,code,name,year',
            ])
            ->latest('id')
            ->limit(
                $safeLimit
            )
            ->get();
    }
}