<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\BnbaImport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface BnbaImportRepositoryInterface
{
    public function create(
        array $data
    ): BnbaImport;

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function insertRows(
        array $rows
    ): void;

    public function findOrFail(
        int $id
    ): BnbaImport;

    public function findForUpdate(
        int $id
    ): BnbaImport;

    public function update(
        BnbaImport $import,
        array $data
    ): BnbaImport;

    public function paginate(
        int $perPage = 15
    ): LengthAwarePaginator;

    public function paginateRows(
        BnbaImport $import,
        ?string $status,
        ?string $search,
        ?string $nikHash,
        int $perPage = 50
    ): LengthAwarePaginator;

    public function confirmableRows(
        BnbaImport $import
    ): Collection;
}