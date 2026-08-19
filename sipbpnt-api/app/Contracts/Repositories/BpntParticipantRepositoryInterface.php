<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\BnbaImport;
use App\Models\BnbaImportRow;
use App\Models\BpntPeriod;
use App\Models\Kpm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BpntParticipantRepositoryInterface
{
    public function existingNikHashesForPeriod(
        int $periodId,
        array $nikHashes
    ): array;

    public function createFromImportRow(
        BpntPeriod $period,
        Kpm $kpm,
        BnbaImport $import,
        BnbaImportRow $row
    ): void;

    public function deleteForPeriod(
        int $periodId
    ): int;

    public function paginateConfirmed(
        array $filters,
        ?string $nikHash = null
    ): LengthAwarePaginator;

    public function filterOptions(
        int $periodId
    ): array;
}