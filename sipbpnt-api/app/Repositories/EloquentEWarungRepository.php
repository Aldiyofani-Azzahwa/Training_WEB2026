<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\EWarungRepositoryInterface;
use App\Models\EWarung;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class EloquentEWarungRepository
    implements EWarungRepositoryInterface
{
    public function all(): Collection
    {
        return EWarung::query()
            ->orderByDesc(
                'is_active'
            )
            ->orderBy(
                'name'
            )
            ->orderBy(
                'id'
            )
            ->get();
    }

    public function findOrFail(
        int $id
    ): EWarung {
        return EWarung::query()
            ->findOrFail(
                $id
            );
    }

    public function create(
        array $data
    ): EWarung {
        return EWarung::query()
            ->create(
                $data
            );
    }

    public function update(
        EWarung $eWarung,
        array $data
    ): EWarung {
        $eWarung
            ->fill(
                $data
            )
            ->save();

        return $eWarung
            ->fresh();
    }

    public function delete(
        EWarung $eWarung
    ): void {
        $eWarung->delete();
    }

    public function isUsedInTransactions(
        int $eWarungId
    ): bool {
        if (
            ! Schema::hasTable(
                'transactions'
            )
            ||
            ! Schema::hasColumn(
                'transactions',
                'e_warung_id'
            )
        ) {
            return false;
        }

        return DB::table(
            'transactions'
        )
            ->where(
                'e_warung_id',
                $eWarungId
            )
            ->exists();
    }
}