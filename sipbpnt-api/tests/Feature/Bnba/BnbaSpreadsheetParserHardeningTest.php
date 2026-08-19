<?php

declare(strict_types=1);

namespace Tests\Feature\Bnba;

use App\Support\Bnba\BnbaSpreadsheetParser;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class BnbaSpreadsheetParserHardeningTest extends TestCase
{
    /** @var array<int, string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach (
            $this->temporaryFiles
            as $path
        ) {
            @unlink($path);
        }

        parent::tearDown();
    }

    public function test_parser_reads_all_rows_across_small_chunks_and_preserves_excel_row_numbers(): void
    {
        config()->set(
            'sipbpnt.bnba_import.chunk_size',
            1
        );

        $path = $this->makeWorkbook([
            $this->validRow([
                'nik' => '1234567891011132',
                'nkk' => '1987654321010123',
                'full_name' => 'KPM SATU',
                'account_number' => '1234566742',
            ]),
            $this->validRow([
                'nik' => '1234567891011133',
                'nkk' => '1987654321010124',
                'full_name' => 'KPM DUA',
                'account_number' => '1234566743',
            ]),
            $this->validRow([
                'nik' => '1234567891011134',
                'nkk' => '1987654321010125',
                'full_name' => 'KPM TIGA',
                'account_number' => '1234566744',
            ]),
        ]);

        $rows = $this->parser()->parse(
            $path,
            2026
        );

        $this->assertCount(3, $rows);

        $this->assertSame(
            [2, 3, 4],
            array_map(
                static fn ($row): int =>
                    $row->rowNumber,
                $rows
            )
        );

        $this->assertSame(
            [
                'KPM SATU',
                'KPM DUA',
                'KPM TIGA',
            ],
            array_map(
                static fn ($row): string =>
                    $row->fullName,
                $rows
            )
        );
    }

    public function test_parser_rejects_fields_that_exceed_database_column_lengths(): void
    {
        $path = $this->makeWorkbook([
            $this->validRow([
                'full_name' => str_repeat(
                    'A',
                    151
                ),
            ]),
        ]);

        $row = $this->parser()
            ->parse($path, 2026)[0];

        $this->assertContains(
            'NAMA LENGKAP maksimal 150 karakter.',
            $row->errors
        );
    }

    public function test_parser_rejects_malformed_rt_rw_and_fractional_welfare_rank(): void
    {
        $path = $this->makeWorkbook([
            $this->validRow([
                'rt' => '12A',
                'rw' => '1234',
                'welfare_rank' => 2.5,
            ]),
        ]);

        $row = $this->parser()
            ->parse($path, 2026)[0];

        $this->assertContains(
            'RT harus berupa 1-3 digit angka.',
            $row->errors
        );

        $this->assertContains(
            'RW harus berupa 1-3 digit angka.',
            $row->errors
        );

        $this->assertContains(
            'PERINGKAT KESEJAHTERAAN KELUARGA harus berupa bilangan bulat non-negatif.',
            $row->errors
        );
    }

    public function test_parser_rejects_numeric_account_number_longer_than_excel_safe_integer_precision(): void
    {
        $path = $this->makeWorkbook(
            [
                $this->validRow(),
            ],
            static function (
                Worksheet $sheet
            ): void {
                $sheet->setCellValueExplicit(
                    'M2',
                    1234567890123456,
                    DataType::TYPE_NUMERIC
                );
            }
        );

        $row = $this->parser()
            ->parse($path, 2026)[0];

        $this->assertContains(
            'Nomor rekening lebih dari 15 digit harus disimpan sebagai teks agar tidak kehilangan presisi di Excel.',
            $row->errors
        );
    }

    public function test_parser_warns_when_safe_length_account_number_is_stored_as_excel_number(): void
    {
        $path = $this->makeWorkbook(
            [
                $this->validRow(),
            ],
            static function (
                Worksheet $sheet
            ): void {
                $sheet->setCellValueExplicit(
                    'M2',
                    1234567890,
                    DataType::TYPE_NUMERIC
                );
            }
        );

        $row = $this->parser()
            ->parse($path, 2026)[0];

        $this->assertContains(
            'Nomor rekening tersimpan sebagai angka Excel. Pastikan tidak memiliki angka nol di bagian awal.',
            $row->warnings
        );
    }

    public function test_parser_rejects_future_birth_date(): void
    {
        $futureYear =
            ((int) date('Y')) + 10;

        $path = $this->makeWorkbook([
            $this->validRow([
                'birth_date' =>
                    '31/12/'.$futureYear,
            ]),
        ]);

        $row = $this->parser()
            ->parse($path, 2026)[0];

        $this->assertContains(
            'Tanggal lahir tidak boleh berada di masa depan.',
            $row->errors
        );
    }

    private function parser():
        BnbaSpreadsheetParser
    {
        return app(
            BnbaSpreadsheetParser::class
        );
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     * @param null|callable(Worksheet): void $afterWrite
     */
    private function makeWorkbook(
        array $rows,
        ?callable $afterWrite = null
    ): string {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet
            ->getActiveSheet();

        $sheet->setTitle('BNBA');

        $sheet->fromArray(
            $this->headers(),
            null,
            'A1'
        );

        foreach (
            $rows
            as $index => $row
        ) {
            $excelRow = $index + 2;

            $sheet->fromArray(
                $row,
                null,
                'A'.$excelRow
            );

            $sheet->setCellValueExplicit(
                'B'.$excelRow,
                (string) $row[1],
                DataType::TYPE_STRING
            );

            $sheet->setCellValueExplicit(
                'C'.$excelRow,
                (string) $row[2],
                DataType::TYPE_STRING
            );

            $sheet->setCellValueExplicit(
                'M'.$excelRow,
                (string) $row[12],
                DataType::TYPE_STRING
            );
        }

        if ($afterWrite !== null) {
            $afterWrite($sheet);
        }

        $temporary = tempnam(
            sys_get_temp_dir(),
            'bnba-parser-hardening-'
        );

        if ($temporary === false) {
            self::fail(
                'Tidak dapat membuat temporary file.'
            );
        }

        @unlink($temporary);

        $path = $temporary.'.xlsx';

        (new Xlsx($spreadsheet))
            ->save($path);

        $spreadsheet
            ->disconnectWorksheets();

        $this->temporaryFiles[] = $path;

        return $path;
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<int, mixed>
     */
    private function validRow(
        array $overrides = []
    ): array {
        $row = array_replace(
            [
                'membership_year' => '2017',
                'nik' => '1234567891011132',
                'nkk' => '1987654321010123',
                'full_name' => 'KPM UJI',
                'birth_place' => 'MOJOKERTO',
                'birth_date' => '23/07/1957',
                'mother_name' => 'IBU KPM',
                'address' => 'ALAMAT UJI',
                'rt' => '001',
                'rw' => '003',
                'kelurahan' => 'JAGALAN',
                'kecamatan' => 'KRANGGAN',
                'account_number' => '1234566742',
                'e_warung_name' => 'E WAROENG UJI',
                'source_status' => 'PENGAJUAN 2026',
                'source_description' => '1 PENERIMA',
                'month_jan' => 'TUB 01',
                'month_feb' => 'TUB 01',
                'month_mar' => 'TUB 02',
                'month_apr' => 'TUB 03',
                'month_may' => 'TUB 03',
                'month_jun' => 'TUB 03',
                'month_jul' => null,
                'month_aug' => null,
                'month_sep' => null,
                'month_oct' => null,
                'month_nov' => null,
                'month_dec' => null,
                'sk_status' => 'SK 2026',
                'sk_description' => '1 PENERIMA',
                'apbn_march_status' =>
                    'YA PBI JKN JANUARI 2026',
                'welfare_rank' => 3,
                'nominal' => 150000,
            ],
            $overrides
        );

        return array_values($row);
    }

    /**
     * @return array<int, string>
     */
    private function headers(): array
    {
        return [
            'TAHUN KEPESERTAAN',
            'NIK',
            'NKK',
            'NAMA LENGKAP',
            'TEMPAT, LAHIR',
            'TANGGAL LAHIR',
            'NAMA IBU KANDUNG',
            'ALAMAT',
            'RT',
            'RW',
            'KELURAHAN',
            'KECAMATAN',
            'NOMOR REKENING',
            'E WAROENG BARU',
            'CEK 2026',
            'KETERANGAN 2026',
            'JAN 2026',
            'FEB 2026',
            'MAR 2026',
            'APR 2026',
            'MEI 2026',
            'JUN 2026',
            'JUL 2026',
            'AGU 2026',
            'SEP 2026',
            'OKT 2026',
            'NOV 2026',
            'DES 2026',
            'CEK SK 2026',
            'KET. 2026',
            'BANSOS APBN MARET 2026',
            'PERINGKAT KESEJAHTERAAN KELUARGA',
            'NOMINAL',
        ];
    }
}