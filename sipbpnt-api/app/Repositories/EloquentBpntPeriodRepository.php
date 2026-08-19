<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\BpntPeriodRepositoryInterface;
use App\Models\BpntPeriod;
use Illuminate\Support\Collection;

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
        return BpntPeriod::query()
            ->withCount([
                'imports',
                'participants',
            ])
            ->with([
                'latestImport',
            ])
            ->findOrFail($id);
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

    public function delete(
        BpntPeriod $period
    ): void {
        $period->delete();
    }

    public function hasImports(
        int $periodId
    ): bool {
        return BpntPeriod::query()
            ->whereKey($periodId)
            ->whereHas('imports')
            ->exists();
    }

    public function hasParticipants(
        int $periodId
    ): bool {
        return BpntPeriod::query()
            ->whereKey($periodId)
            ->whereHas('participants')
            ->exists();
    }

    public function all(): Collection
    {
        return BpntPeriod::query()
            ->withCount([
                'imports',
                'participants',
            ])
            ->with([
                'latestImport',
            ])
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->get();
    }
}