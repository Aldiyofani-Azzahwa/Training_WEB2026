<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

interface AuditLogRepositoryInterface
{
    public function record(
        array $data
    ): void;
}