<?php

declare(strict_types=1);

namespace App\Exceptions\Bnba;

use RuntimeException;

final class BnbaImportIntegrityException
    extends RuntimeException
{
    public function __construct(
        public readonly int $importId,
        public readonly int $periodId,
        public readonly string $reason,
        string $message,
    ) {
        parent::__construct($message);
    }
}