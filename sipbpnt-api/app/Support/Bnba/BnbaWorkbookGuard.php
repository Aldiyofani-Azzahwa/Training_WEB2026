<?php

declare(strict_types=1);

namespace App\Support\Bnba;

use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;

final class BnbaWorkbookGuard
{
    public function assertImportable(
        string $path
    ): void {
        try {
            $type = IOFactory::identify(
                $path,
                [
                    IOFactory::READER_XLSX,
                    IOFactory::READER_XLS,
                ]
            );

            $reader =
                IOFactory::createReader(
                    $type
                );

            $worksheets =
                $reader
                    ->listWorksheetInfo(
                        $path
                    );
        } catch (ReaderException) {
            throw ValidationException
                ::withMessages([
                    'file' => [
                        'File BNBA tidak dapat '
                        .'dibaca sebagai workbook '
                        .'Excel .xlsx atau .xls '
                        .'yang valid.',
                    ],
                ]);
        }

        if ($worksheets === []) {
            throw ValidationException
                ::withMessages([
                    'file' => [
                        'Workbook BNBA tidak '
                        .'memiliki worksheet.',
                    ],
                ]);
        }

        $maxWorksheets =
            $this->maxWorksheets();

        if (
            count($worksheets)
            > $maxWorksheets
        ) {
            throw ValidationException
                ::withMessages([
                    'file' => [
                        'Jumlah worksheet BNBA '
                        .'melebihi batas teknis '
                        .$maxWorksheets
                        .'.',
                    ],
                ]);
        }

        foreach (
            $worksheets
            as $worksheet
        ) {
            $this->assertWorksheetWithinLimits(
                $worksheet
            );
        }
    }

    /**
     * @param array<string, mixed> $worksheet
     */
    private function assertWorksheetWithinLimits(
        array $worksheet
    ): void {
        $name = trim(
            (string) (
                $worksheet[
                    'worksheetName'
                ]
                ?? 'Worksheet'
            )
        );

        $totalRows = max(
            0,
            (int) (
                $worksheet[
                    'totalRows'
                ]
                ?? 0
            )
        );

        $totalColumns = max(
            0,
            (int) (
                $worksheet[
                    'totalColumns'
                ]
                ?? 0
            )
        );

        $dataRows = max(
            0,
            $totalRows - 1
        );

        if (
            $dataRows
            > $this->maxRows()
        ) {
            throw ValidationException
                ::withMessages([
                    'file' => [
                        "Worksheet {$name} "
                        .'memiliki terlalu banyak '
                        .'baris data. Maksimal '
                        .$this->maxRows()
                        .' baris data.',
                    ],
                ]);
        }

        if (
            $totalColumns
            > $this->maxColumns()
        ) {
            throw ValidationException
                ::withMessages([
                    'file' => [
                        "Worksheet {$name} "
                        .'memiliki terlalu banyak '
                        .'kolom. Maksimal '
                        .$this->maxColumns()
                        .' kolom.',
                    ],
                ]);
        }
    }

    private function maxRows(): int
    {
        return max(
            1,
            (int) config(
                'sipbpnt.bnba_import.max_rows',
                20000
            )
        );
    }

    private function maxColumns(): int
    {
        return max(
            33,
            (int) config(
                'sipbpnt.bnba_import.max_columns',
                64
            )
        );
    }

    private function maxWorksheets(): int
    {
        return max(
            1,
            (int) config(
                'sipbpnt.bnba_import.max_worksheets',
                5
            )
        );
    }
}