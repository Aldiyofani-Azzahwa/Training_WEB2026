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
        return BpntPeriod::query()
            ->create($data);
    }

    public function findOrFail(
        int $id
    ): BpntPeriod {
        return BpntPeriod::query()
            ->findOrFail($id);
    }

    public function all(): Collection
    {
        return BpntPeriod::query()
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->get();
    }
}