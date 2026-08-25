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

    public function findForUpdate(
        int $id
    ): BpntPeriod;

    public function active(): ?BpntPeriod;

    public function update(
        BpntPeriod $period,
        array $data
    ): BpntPeriod;

    public function activateExclusive(
        BpntPeriod $period
    ): BpntPeriod;

    public function deactivate(
        BpntPeriod $period
    ): BpntPeriod;

    public function delete(
        BpntPeriod $period
    ): void;

    public function hasImports(
        int $periodId
    ): bool;

    public function hasParticipants(
        int $periodId
    ): bool;

    public function hasAssignments(
        int $periodId
    ): bool;

    public function all(): Collection;
}