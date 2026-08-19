<?php

declare(strict_types=1);

namespace App\Support\Bnba;

use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

final readonly class BnbaChunkReadFilter implements IReadFilter
{
    public function __construct(
        private int $startRow,
        private int $endRow,
        private ?string $worksheetName = null,
    ) {
        if ($this->startRow < 1) {
            throw new InvalidArgumentException(
                'Start row harus minimal 1.'
            );
        }

        if ($this->endRow < $this->startRow) {
            throw new InvalidArgumentException(
                'End row tidak boleh lebih kecil dari start row.'
            );
        }
    }

    public function readCell(
        string $columnAddress,
        int $row,
        string $worksheetName = ''
    ): bool {
        if (
            $this->worksheetName !== null
            && $worksheetName !== $this->worksheetName
        ) {
            return false;
        }

        return $row >= $this->startRow
            && $row <= $this->endRow;
    }
}