<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\EWarung;
use Illuminate\Support\Collection;

interface EWarungRepositoryInterface
{
    public function all(): Collection;

    public function findOrFail(int $id): EWarung;

    public function create(array $data): EWarung;

    public function update(
        EWarung $eWarung,
        array $data
    ): EWarung;

    public function delete(
        EWarung $eWarung
    ): void;

    public function isUsedInTransactions(
        int $eWarungId
    ): bool;
}