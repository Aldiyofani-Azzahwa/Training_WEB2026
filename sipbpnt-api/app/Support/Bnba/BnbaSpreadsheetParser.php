<?php

declare(strict_types=1);

namespace App\Support\Bnba;

use App\DTOs\Bnba\BnbaRowData;
use DateTimeImmutable;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

final class BnbaSpreadsheetParser
{
    private const MAX_ACCOUNT_DIGITS = 30;

    private const MAX_EXCEL_SAFE_INTEGER_DIGITS = 15;

    public function __construct(
        private readonly BnbaHeaderMap $headerMap,
    ) {}

    /**
     * @return array<int, BnbaRowData>
     */
    public function parse(
        string $path,
        int $periodYear
    ): array {
        $reader = IOFactory::createReaderForFile(
            $path
        );

        $reader->setReadDataOnly(false);
        $reader->setReadEmptyCells(false);

        $worksheetInfo =
            $reader->listWorksheetInfo(
                $path
            );

        /*
         * Pertama hanya baca row header.
         */
        $reader->setReadFilter(
            new BnbaChunkReadFilter(
                startRow: 1,
                endRow: 1,
            )
        );

        $headerSpreadsheet =
            $reader->load($path);

        try {
            $headerSheet =
                $headerSpreadsheet
                    ->getActiveSheet();

            $worksheetName =
                $headerSheet->getTitle();

            $columns =
                $this->headerMap->resolve(
                    $headerSheet,
                    $periodYear
                );
        } finally {
            $headerSpreadsheet
                ->disconnectWorksheets();
        }

        $highestRow =
            $this->highestRowForWorksheet(
                $worksheetInfo,
                $worksheetName
            );

        if ($highestRow < 2) {
            return [];
        }

        /*
         * Setelah worksheet aktif diketahui,
         * hanya worksheet tersebut yang dibaca.
         */
        $reader->setLoadSheetsOnly([
            $worksheetName,
        ]);

        $rows = [];
        $chunkSize = $this->chunkSize();

        for (
            $chunkStart = 2;
            $chunkStart <= $highestRow;
            $chunkStart += $chunkSize
        ) {
            $chunkEnd = min(
                $highestRow,
                $chunkStart + $chunkSize - 1
            );

            $reader->setReadFilter(
                new BnbaChunkReadFilter(
                    startRow: $chunkStart,
                    endRow: $chunkEnd,
                    worksheetName: $worksheetName,
                )
            );

            $spreadsheet =
                $reader->load($path);

            try {
                $sheet =
                    $spreadsheet
                        ->getSheetByName(
                            $worksheetName
                        );

                if ($sheet === null) {
                    throw new \RuntimeException(
                        'Worksheet BNBA aktif tidak dapat dibaca.'
                    );
                }

                for (
                    $rowNumber = $chunkStart;
                    $rowNumber <= $chunkEnd;
                    $rowNumber++
                ) {
                    if (
                        $this->isBlankRow(
                            $sheet,
                            $columns,
                            $rowNumber
                        )
                    ) {
                        continue;
                    }

                    $rows[] =
                        $this->parseRow(
                            $sheet,
                            $columns,
                            $rowNumber
                        );
                }
            } finally {
                $spreadsheet
                    ->disconnectWorksheets();
            }
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $worksheetInfo
     */
    private function highestRowForWorksheet(
        array $worksheetInfo,
        string $worksheetName
    ): int {
        foreach (
            $worksheetInfo
            as $worksheet
        ) {
            if (
                (string) (
                    $worksheet['worksheetName']
                    ?? ''
                ) !== $worksheetName
            ) {
                continue;
            }

            return max(
                1,
                (int) (
                    $worksheet['totalRows']
                    ?? 1
                )
            );
        }

        throw new \RuntimeException(
            'Informasi worksheet BNBA aktif tidak ditemukan.'
        );
    }

    private function chunkSize(): int
    {
        return min(
            2000,
            max(
                1,
                (int) config(
                    'sipbpnt.bnba_import.chunk_size',
                    500
                )
            )
        );
    }

    /**
     * @param array<string, int> $columns
     */
    private function parseRow(
        Worksheet $sheet,
        array $columns,
        int $rowNumber
    ): BnbaRowData {
        $errors = [];
        $warnings = [];

        $this->rejectUnexpectedFormulas(
            $sheet,
            $columns,
            $rowNumber,
            $errors
        );

        $membershipYear =
            $this->stringValue(
                $this->cell(
                    $sheet,
                    $columns,
                    'membership_year',
                    $rowNumber
                )
            );

        $nikCell = $this->cell(
            $sheet,
            $columns,
            'nik',
            $rowNumber
        );

        $nkkCell = $this->cell(
            $sheet,
            $columns,
            'nkk',
            $rowNumber
        );

        $nik = $this->identifierValue(
            $nikCell,
            'NIK',
            $errors
        );

        $nkk = $this->identifierValue(
            $nkkCell,
            'NKK',
            $errors
        );

        $fullName =
            $this->stringValue(
                $this->cell(
                    $sheet,
                    $columns,
                    'full_name',
                    $rowNumber
                )
            );

        $birthPlace =
            $this->nullableStringValue(
                $this->cell(
                    $sheet,
                    $columns,
                    'birth_place',
                    $rowNumber
                )
            );

        $birthDateCell =
            $this->cell(
                $sheet,
                $columns,
                'birth_date',
                $rowNumber
            );

        $birthDate =
            $this->dateValue(
                $birthDateCell,
                $errors
            );

        $motherName =
            $this->nullableStringValue(
                $this->cell(
                    $sheet,
                    $columns,
                    'mother_name',
                    $rowNumber
                )
            );

        $address =
            $this->stringValue(
                $this->cell(
                    $sheet,
                    $columns,
                    'address',
                    $rowNumber
                )
            );

        $rtCell = $this->cell(
            $sheet,
            $columns,
            'rt',
            $rowNumber
        );

        $rt = $this->rtRwValue(
            $rtCell,
            'RT',
            $errors
        );

        $rwCell = $this->cell(
            $sheet,
            $columns,
            'rw',
            $rowNumber
        );

        $rw = $this->rtRwValue(
            $rwCell,
            'RW',
            $errors
        );

        $kelurahan =
            $this->stringValue(
                $this->cell(
                    $sheet,
                    $columns,
                    'kelurahan',
                    $rowNumber
                )
            );

        $kecamatan =
            $this->stringValue(
                $this->cell(
                    $sheet,
                    $columns,
                    'kecamatan',
                    $rowNumber
                )
            );

        $accountNumber =
            $this->accountValue(
                $this->cell(
                    $sheet,
                    $columns,
                    'account_number',
                    $rowNumber
                ),
                $errors,
                $warnings
            );

        $eWarungName =
            $this->stringValue(
                $this->cell(
                    $sheet,
                    $columns,
                    'e_warung_name',
                    $rowNumber
                )
            );

        $sourceStatus =
            $this->nullableStringValue(
                $this->cell(
                    $sheet,
                    $columns,
                    'source_status',
                    $rowNumber
                )
            );

        $sourceDescription =
            $this->nullableStringValue(
                $this->cell(
                    $sheet,
                    $columns,
                    'source_description',
                    $rowNumber
                )
            );

        $monthlyStatuses = [];

        foreach ([
            'jan',
            'feb',
            'mar',
            'apr',
            'may',
            'jun',
            'jul',
            'aug',
            'sep',
            'oct',
            'nov',
            'dec',
        ] as $month) {
            $monthlyStatuses[$month] =
                $this->nullableStringValue(
                    $this->cell(
                        $sheet,
                        $columns,
                        'month_'.$month,
                        $rowNumber
                    )
                );
        }

        $skStatus =
            $this->nullableStringValue(
                $this->cell(
                    $sheet,
                    $columns,
                    'sk_status',
                    $rowNumber
                )
            );

        $skDescription =
            $this->nullableStringValue(
                $this->cell(
                    $sheet,
                    $columns,
                    'sk_description',
                    $rowNumber
                )
            );

        $apbnMarchStatus =
            $this->nullableStringValue(
                $this->cell(
                    $sheet,
                    $columns,
                    'apbn_march_status',
                    $rowNumber
                )
            );

        $welfareRank =
            $this->integerValue(
                $this->cell(
                    $sheet,
                    $columns,
                    'welfare_rank',
                    $rowNumber
                ),
                'PERINGKAT KESEJAHTERAAN KELUARGA',
                $errors
            );

        $nominal =
            $this->nominalValue(
                $this->cell(
                    $sheet,
                    $columns,
                    'nominal',
                    $rowNumber
                ),
                $errors
            );

        $this->validateRequired(
            $membershipYear,
            $nik,
            $nkk,
            $fullName,
            $address,
            $kelurahan,
            $kecamatan,
            $accountNumber,
            $eWarungName,
            $nominal,
            $errors
        );

        $this->validateFieldLengths(
            fullName: $fullName,
            birthPlace: $birthPlace,
            motherName: $motherName,
            kelurahan: $kelurahan,
            kecamatan: $kecamatan,
            eWarungName: $eWarungName,
            sourceStatus: $sourceStatus,
            sourceDescription: $sourceDescription,
            skStatus: $skStatus,
            skDescription: $skDescription,
            apbnMarchStatus: $apbnMarchStatus,
            errors: $errors,
        );

        if ($birthPlace === null) {
            $warnings[] =
                'Tempat lahir kosong.';
        }

        if (
            $birthDate === null
            && $this->isCellBlank(
                $birthDateCell
            )
        ) {
            $warnings[] =
                'Tanggal lahir kosong.';
        }

        if ($motherName === null) {
            $warnings[] =
                'Nama ibu kandung kosong.';
        }

        if ($this->isCellBlank($rtCell)) {
            $warnings[] = 'RT kosong.';
        }

        if ($this->isCellBlank($rwCell)) {
            $warnings[] = 'RW kosong.';
        }

        if ($sourceStatus === null) {
            $warnings[] =
                'Status sumber BNBA kosong.';
        }

        if (
            $welfareRank !== null
            && ! in_array(
                $welfareRank,
                [1, 2, 3, 4],
                true
            )
        ) {
            $warnings[] =
                'Peringkat kesejahteraan '
                .'di luar rentang 1-4.';
        }

        return new BnbaRowData(
            rowNumber: $rowNumber,
            membershipYear: $membershipYear,
            nik: $nik,
            nkk: $nkk,
            fullName: $fullName,
            birthPlace: $birthPlace,
            birthDate: $birthDate,
            motherName: $motherName,
            address: $address,
            rt: $rt,
            rw: $rw,
            kelurahan: $kelurahan,
            kecamatan: $kecamatan,
            accountNumber: $accountNumber,
            eWarungName: $eWarungName,
            sourceStatus: $sourceStatus,
            sourceDescription: $sourceDescription,
            monthlyStatuses: $monthlyStatuses,
            skStatus: $skStatus,
            skDescription: $skDescription,
            apbnMarchStatus: $apbnMarchStatus,
            welfareRank: $welfareRank,
            nominal: $nominal,
            errors: array_values(
                array_unique($errors)
            ),
            warnings: array_values(
                array_unique($warnings)
            ),
        );
    }

    /**
     * @param array<string, int> $columns
     * @param array<int, string> $errors
     */
    private function rejectUnexpectedFormulas(
        Worksheet $sheet,
        array $columns,
        int $rowNumber,
        array &$errors
    ): void {
        foreach (
            $columns
            as $key => $column
        ) {
            if ($key === 'nominal') {
                continue;
            }

            $coordinate =
                Coordinate::stringFromColumnIndex(
                    $column
                ).$rowNumber;

            $cell =
                $sheet->getCell(
                    $coordinate
                );

            if (
                $cell->getDataType()
                === DataType::TYPE_FORMULA
            ) {
                $errors[] =
                    "Formula Excel tidak diizinkan "
                    ."pada kolom {$key}.";
            }
        }
    }

    /**
     * @param array<string, int> $columns
     */
    private function isBlankRow(
        Worksheet $sheet,
        array $columns,
        int $rowNumber
    ): bool {
        foreach (
            $columns
            as $key => $column
        ) {
            $cell = $this->cell(
                $sheet,
                $columns,
                $key,
                $rowNumber
            );

            if (! $this->isCellBlank($cell)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, int> $columns
     */
    private function cell(
        Worksheet $sheet,
        array $columns,
        string $key,
        int $rowNumber
    ): Cell {
        $coordinate =
            Coordinate::stringFromColumnIndex(
                $columns[$key]
            ).$rowNumber;

        return $sheet->getCell(
            $coordinate
        );
    }

    /**
     * @param array<int, string> $errors
     */
    private function identifierValue(
        Cell $cell,
        string $label,
        array &$errors
    ): string {
        if (
            $cell->getDataType()
            === DataType::TYPE_FORMULA
        ) {
            $errors[] =
                "{$label} tidak boleh "
                .'berupa formula Excel.';

            return '';
        }

        if (
            $cell->getDataType()
            === DataType::TYPE_NUMERIC
        ) {
            $errors[] =
                "{$label} harus disimpan "
                .'sebagai teks agar 16 digit '
                .'tidak rusak oleh Excel.';

            $value = sprintf(
                '%.0f',
                (float) $cell->getValue()
            );
        } else {
            $value = (string) (
                $cell->getValue() ?? ''
            );
        }

        $value = preg_replace(
            '/\s+/u',
            '',
            trim($value)
        ) ?? trim($value);

        if (
            $value !== ''
            && preg_match(
                '/^\d{16}$/',
                $value
            ) !== 1
        ) {
            $errors[] =
                "{$label} harus tepat 16 digit.";
        }

        return $value;
    }

    /**
     * @param array<int, string> $errors
     * @param array<int, string> $warnings
     */
    private function accountValue(
        Cell $cell,
        array &$errors,
        array &$warnings
    ): string {
        if (
            $cell->getDataType()
            === DataType::TYPE_FORMULA
        ) {
            $errors[] =
                'Nomor rekening tidak boleh '
                .'berupa formula Excel.';

            return '';
        }

        $value = $cell->getValue();

        if ($value === null) {
            return '';
        }

        if (
            is_int($value)
            || is_float($value)
        ) {
            $numeric = (float) $value;

            if (
                ! is_finite($numeric)
                || $numeric < 0
                || floor($numeric) !== $numeric
            ) {
                $errors[] =
                    'Nomor rekening numerik '
                    .'harus berupa bilangan bulat '
                    .'non-negatif.';

                return '';
            }

            $normalized = sprintf(
                '%.0f',
                $numeric
            );

            if (
                strlen($normalized)
                > self::MAX_EXCEL_SAFE_INTEGER_DIGITS
            ) {
                $errors[] =
                    'Nomor rekening lebih dari 15 digit '
                    .'harus disimpan sebagai teks agar '
                    .'tidak kehilangan presisi di Excel.';
            } else {
                $warnings[] =
                    'Nomor rekening tersimpan sebagai '
                    .'angka Excel. Pastikan tidak memiliki '
                    .'angka nol di bagian awal.';
            }

            return $normalized;
        }

        $normalized = preg_replace(
            '/\s+/u',
            '',
            trim((string) $value)
        ) ?? trim((string) $value);

        if (
            $normalized !== ''
            && preg_match(
                '/^\d+$/',
                $normalized
            ) !== 1
        ) {
            $errors[] =
                'Nomor rekening hanya boleh '
                .'berisi digit angka.';
        }

        if (
            strlen($normalized)
            > self::MAX_ACCOUNT_DIGITS
        ) {
            $errors[] =
                'Nomor rekening maksimal '
                .self::MAX_ACCOUNT_DIGITS
                .' digit.';
        }

        return $normalized;
    }

    /**
     * @param array<int, string> $errors
     */
    private function dateValue(
        Cell $cell,
        array &$errors
    ): ?DateTimeImmutable {
        $raw = $cell->getValue();

        if (
            $raw === null
            || trim((string) $raw) === ''
        ) {
            return null;
        }

        if (
            $cell->getDataType()
            === DataType::TYPE_FORMULA
        ) {
            $errors[] =
                'Tanggal lahir tidak boleh '
                .'berupa formula Excel.';

            return null;
        }

        $date = null;

        if (
            is_numeric($raw)
            && ExcelDate::isDateTime($cell)
        ) {
            $date =
                DateTimeImmutable::createFromMutable(
                    ExcelDate::excelToDateTimeObject(
                        (float) $raw
                    )
                )->setTime(0, 0);
        } else {
            foreach (
                [
                    '!d/m/Y',
                    '!d-m-Y',
                    '!Y-m-d',
                ]
                as $format
            ) {
                $candidate =
                    DateTimeImmutable::createFromFormat(
                        $format,
                        trim((string) $raw)
                    );

                $dateErrors =
                    DateTimeImmutable::getLastErrors();

                if (
                    $candidate !== false
                    && (
                        $dateErrors === false
                        || (
                            $dateErrors['warning_count'] === 0
                            && $dateErrors['error_count'] === 0
                        )
                    )
                ) {
                    $date = $candidate;

                    break;
                }
            }
        }

        if ($date === null) {
            $errors[] =
                'Tanggal lahir tidak valid.';

            return null;
        }

        if (
            $date
            > new DateTimeImmutable('today')
        ) {
            $errors[] =
                'Tanggal lahir tidak boleh '
                .'berada di masa depan.';
        }

        return $date;
    }

    /**
     * @param array<int, string> $errors
     */
    private function nominalValue(
        Cell $cell,
        array &$errors
    ): ?int {
        $raw = $cell->getValue();

        if (
            $raw === null
            || trim((string) $raw) === ''
        ) {
            return null;
        }

        if (
            $cell->getDataType()
            === DataType::TYPE_FORMULA
        ) {
            $formula =
                trim((string) $raw);

            if (
                preg_match(
                    '/^=\s*\d+(?:\.\d+)?'
                    .'(?:\s*[+\-*\/]\s*'
                    .'\d+(?:\.\d+)?)+\s*$/',
                    $formula
                ) !== 1
            ) {
                $errors[] =
                    'Formula NOMINAL hanya '
                    .'boleh berupa operasi '
                    .'aritmetika sederhana.';

                return null;
            }

            try {
                $raw =
                    $cell
                        ->getCalculatedValue();
            } catch (Throwable) {
                $errors[] =
                    'Formula NOMINAL '
                    .'tidak dapat dihitung.';

                return null;
            }
        }

        if (! is_numeric($raw)) {
            $errors[] =
                'NOMINAL harus berupa angka.';

            return null;
        }

        $value = (float) $raw;

        if (! is_finite($value)) {
            $errors[] =
                'NOMINAL tidak valid.';

            return null;
        }

        if (
            $value < 0
            || floor($value) !== $value
        ) {
            $errors[] =
                'NOMINAL harus berupa '
                .'bilangan bulat non-negatif.';

            return null;
        }

        if ($value > PHP_INT_MAX) {
            $errors[] =
                'NOMINAL melebihi batas '
                .'angka yang dapat diproses sistem.';

            return null;
        }

        return (int) $value;
    }

    /**
     * @param array<int, string> $errors
     */
    private function integerValue(
        Cell $cell,
        string $label,
        array &$errors
    ): ?int {
        $value = $cell->getValue();

        if (
            $value === null
            || trim((string) $value) === ''
        ) {
            return null;
        }

        if (
            $cell->getDataType()
            === DataType::TYPE_FORMULA
        ) {
            return null;
        }

        if (! is_numeric($value)) {
            $errors[] =
                "{$label} harus berupa bilangan bulat.";

            return null;
        }

        $numeric = (float) $value;

        if (
            ! is_finite($numeric)
            || $numeric < 0
            || floor($numeric) !== $numeric
            || $numeric > PHP_INT_MAX
        ) {
            $errors[] =
                "{$label} harus berupa bilangan bulat "
                .'non-negatif.';

            return null;
        }

        return (int) $numeric;
    }

    private function stringValue(
        Cell $cell
    ): string {
        return trim(
            (string) (
                $cell->getValue()
                ?? ''
            )
        );
    }

    private function nullableStringValue(
        Cell $cell
    ): ?string {
        $value =
            $this->stringValue($cell);

        return $value === ''
            ? null
            : $value;
    }

    private function isCellBlank(
        Cell $cell
    ): bool {
        $value = $cell->getValue();

        return $value === null
            || trim((string) $value) === '';
    }

    /**
     * @param array<int, string> $errors
     */
    private function rtRwValue(
        Cell $cell,
        string $label,
        array &$errors
    ): ?string {
        $value = $cell->getValue();

        if (
            $value === null
            || trim((string) $value) === ''
        ) {
            return null;
        }

        if (
            $cell->getDataType()
            === DataType::TYPE_FORMULA
        ) {
            return null;
        }

        if (is_numeric($value)) {
            $numeric = (float) $value;

            if (
                ! is_finite($numeric)
                || $numeric < 0
                || floor($numeric) !== $numeric
                || $numeric > 999
            ) {
                $errors[] =
                    "{$label} harus berupa 1-3 digit angka.";

                return null;
            }

            return str_pad(
                (string) ((int) $numeric),
                3,
                '0',
                STR_PAD_LEFT
            );
        }

        $string = preg_replace(
            '/\s+/u',
            '',
            trim((string) $value)
        ) ?? trim((string) $value);

        if (
            preg_match(
                '/^\d{1,3}$/',
                $string
            ) !== 1
        ) {
            $errors[] =
                "{$label} harus berupa 1-3 digit angka.";

            return null;
        }

        return str_pad(
            $string,
            3,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * @param array<int, string> $errors
     */
    private function validateFieldLengths(
        string $fullName,
        ?string $birthPlace,
        ?string $motherName,
        string $kelurahan,
        string $kecamatan,
        string $eWarungName,
        ?string $sourceStatus,
        ?string $sourceDescription,
        ?string $skStatus,
        ?string $skDescription,
        ?string $apbnMarchStatus,
        array &$errors
    ): void {
        $fields = [
            'NAMA LENGKAP' => [
                $fullName,
                150,
            ],
            'TEMPAT LAHIR' => [
                $birthPlace,
                100,
            ],
            'NAMA IBU KANDUNG' => [
                $motherName,
                150,
            ],
            'KELURAHAN' => [
                $kelurahan,
                100,
            ],
            'KECAMATAN' => [
                $kecamatan,
                100,
            ],
            'E WAROENG BARU' => [
                $eWarungName,
                200,
            ],
            'CEK' => [
                $sourceStatus,
                100,
            ],
            'KETERANGAN' => [
                $sourceDescription,
                255,
            ],
            'CEK SK' => [
                $skStatus,
                100,
            ],
            'KET SK' => [
                $skDescription,
                255,
            ],
            'BANSOS APBN MARET' => [
                $apbnMarchStatus,
                255,
            ],
        ];

        foreach (
            $fields
            as $label => [$value, $maxLength]
        ) {
            if (
                $value !== null
                && mb_strlen($value)
                    > $maxLength
            ) {
                $errors[] =
                    "{$label} maksimal "
                    ."{$maxLength} karakter.";
            }
        }
    }

    /**
     * @param array<int, string> $errors
     */
    private function validateRequired(
        string $membershipYear,
        string $nik,
        string $nkk,
        string $fullName,
        string $address,
        string $kelurahan,
        string $kecamatan,
        string $accountNumber,
        string $eWarungName,
        ?int $nominal,
        array &$errors
    ): void {
        $required = [
            'TAHUN KEPESERTAAN'
                => $membershipYear,

            'NIK'
                => $nik,

            'NKK'
                => $nkk,

            'NAMA LENGKAP'
                => $fullName,

            'ALAMAT'
                => $address,

            'KELURAHAN'
                => $kelurahan,

            'KECAMATAN'
                => $kecamatan,

            'NOMOR REKENING'
                => $accountNumber,

            'E WAROENG BARU'
                => $eWarungName,
        ];

        foreach (
            $required
            as $label => $value
        ) {
            if ($value === '') {
                $errors[] =
                    "{$label} wajib diisi.";
            }
        }

        if (
            $membershipYear !== ''
            && preg_match(
                '/^\d{4}$/',
                $membershipYear
            ) !== 1
        ) {
            $errors[] =
                'TAHUN KEPESERTAAN '
                .'harus 4 digit.';
        }

        if ($nominal === null) {
            $errors[] =
                'NOMINAL wajib diisi '
                .'dan harus valid.';
        }
    }
}