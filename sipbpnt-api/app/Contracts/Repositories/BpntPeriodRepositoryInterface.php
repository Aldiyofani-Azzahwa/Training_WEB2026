<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\BpntPeriod;
use Illuminate\Support\Collection;

interface BpntPeriodRepositoryInterface
{
    public function create(
        array $data
    ): BpntPeriod;

    public function findOrFail(
        int $id
    ): BpntPeriod;

    public function all(): Collection;
}