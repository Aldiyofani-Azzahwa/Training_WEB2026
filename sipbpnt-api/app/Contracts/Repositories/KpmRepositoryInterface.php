<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\BnbaImportRow;
use App\Models\Kpm;

interface KpmRepositoryInterface
{
    public function upsertFromImportRow(
        BnbaImportRow $row
    ): Kpm;
}