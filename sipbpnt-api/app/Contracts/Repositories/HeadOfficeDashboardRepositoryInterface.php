<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

interface HeadOfficeDashboardRepositoryInterface
{
    public function summary(
        int $periodId,
        ?int $kecamatanId = null,
        ?int $kelurahanId = null
    ): array;

    public function regions(int $periodId): array;

    public function dailyTransactions(
        int $periodId,
        ?int $kecamatanId = null,
        ?int $kelurahanId = null
    ): array;

    public function resolveScope(
        ?int $kecamatanId = null,
        ?int $kelurahanId = null
    ): array;
}