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
    /**
     * @param array<int, string> $nikHashes
     * @return array<int, string>
     */
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

    /**
     * @param array<string, mixed> $filters
     */
    public function paginateConfirmed(
        array $filters,
        ?string $nikHash = null
    ): LengthAwarePaginator;

    /**
     * @return array{
     *     kecamatan: array<int, string>,
     *     kelurahan: array<int, string>,
     *     e_warungs: array<int, string>
     * }
     */
    public function filterOptions(
        int $periodId
    ): array;
}