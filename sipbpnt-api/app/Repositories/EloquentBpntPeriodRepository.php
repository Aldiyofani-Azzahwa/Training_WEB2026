<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\BpntPeriodRepositoryInterface;
use App\Enums\BnbaImportStatus;
use App\Models\BpntPeriod;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class EloquentBpntPeriodRepository
    implements BpntPeriodRepositoryInterface
{
    public function create(
        array $data
    ): BpntPeriod {
        $period =
            BpntPeriod::query()
                ->create($data);

        return $this->findOrFail(
            $period->id
        );
    }

    public function findOrFail(
        int $id
    ): BpntPeriod {
        return $this->baseQuery()
            ->findOrFail(
                $id
            );
    }

    public function findForUpdate(
        int $id
    ): BpntPeriod {
        return $this->baseQuery()
            ->whereKey(
                $id
            )
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function active(): ?BpntPeriod
    {
        return $this->baseQuery()
            ->where(
                'is_active',
                true
            )
            ->where(
                'active_slot',
                1
            )
            ->first();
    }

    public function update(
        BpntPeriod $period,
        array $data
    ): BpntPeriod {
        $period
            ->fill($data)
            ->save();

        return $this->findOrFail(
            $period->id
        );
    }

    public function activateExclusive(
        BpntPeriod $period
    ): BpntPeriod {
        /*
         * Gunakan urutan ID yang konsisten ketika
         * mengunci seluruh periode untuk mengurangi
         * risiko deadlock antar-request aktivasi.
         */
        BpntPeriod::query()
            ->orderBy('id')
            ->lockForUpdate()
            ->get([
                'id',
            ]);

        /*
         * Data periode yang diterima service mungkin
         * dibaca sebelum request lain menghapus BNBA.
         *
         * Setelah lock diperoleh, data harus dibaca
         * ulang dan syarat aktivasi diperiksa kembali.
         */
        $lockedPeriod =
            $this->findForUpdate(
                $period->id
            );

        $latestImport =
            $lockedPeriod
                ->latestImport;

        if (
            $latestImport === null
            ||
            $latestImport->status
                !== BnbaImportStatus::CONFIRMED
            ||
            (int) $lockedPeriod
                ->participants_count
                <= 0
        ) {
            throw ValidationException
                ::withMessages([
                    'period' => [
                        'Periode hanya dapat diaktifkan setelah BNBA dikonfirmasi dan data KPM tersedia.',
                    ],
                ]);
        }

        BpntPeriod::query()
            ->where(
                'is_active',
                true
            )
            ->orWhereNotNull(
                'active_slot'
            )
            ->update([
                'is_active'
                    => false,

                'active_slot'
                    => null,
            ]);

        BpntPeriod::query()
            ->whereKey(
                $lockedPeriod->id
            )
            ->update([
                'is_active'
                    => true,

                'active_slot'
                    => 1,
            ]);

        return $this->findOrFail(
            $lockedPeriod->id
        );
    }

    public function deactivate(
        BpntPeriod $period
    ): BpntPeriod {
        BpntPeriod::query()
            ->whereKey(
                $period->id
            )
            ->update([
                'is_active'
                    => false,

                'active_slot'
                    => null,
            ]);

        return $this->findOrFail(
            $period->id
        );
    }

    public function delete(
        BpntPeriod $period
    ): void {
        $period->delete();
    }

    public function hasImports(
        int $periodId
    ): bool {
        return BpntPeriod::query()
            ->whereKey(
                $periodId
            )
            ->whereHas(
                'imports'
            )
            ->exists();
    }

    public function hasParticipants(
        int $periodId
    ): bool {
        return BpntPeriod::query()
            ->whereKey(
                $periodId
            )
            ->whereHas(
                'participants'
            )
            ->exists();
    }

    public function hasAssignments(
        int $periodId
    ): bool {
        return BpntPeriod::query()
            ->whereKey(
                $periodId
            )
            ->whereHas(
                'assignments'
            )
            ->exists();
    }

    public function all(): Collection
    {
        return $this->baseQuery()
            ->orderByDesc(
                'is_active'
            )
            ->orderByDesc(
                'year'
            )
            ->orderByDesc(
                'id'
            )
            ->get();
    }

    private function baseQuery()
    {
        return BpntPeriod::query()
            ->withCount([
                'imports',
                'participants',
                'assignments',
            ])
            ->with([
                'latestImport',
            ]);
    }
}