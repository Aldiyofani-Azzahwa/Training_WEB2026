<?php

declare(strict_types=1);

namespace Tests\Feature\Bnba;

use App\Enums\UserRole;
use App\Models\BpntPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class BnbaImportHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'sipbpnt.identity_hash_key',
            'testing-identity-hash-key-32-bytes-minimum'
        );

        config()->set(
            'sipbpnt.bnba_import.max_file_kb',
            10240
        );

        config()->set(
            'sipbpnt.bnba_import.max_rows',
            20000
        );

        config()->set(
            'sipbpnt.bnba_import.max_columns',
            64
        );

        config()->set(
            'sipbpnt.bnba_import.max_worksheets',
            5
        );

        config()->set(
            'sipbpnt.bnba_import.chunk_size',
            500
        );
    }

    public function test_valid_workbook_passes_preflight_and_uploads(): void
    {
        Storage::fake('local');

        $admin =
            $this->createAdmin();

        $period =
            $this->createPeriod();

        $file =
            $this->makeWorkbook(
                1
            );

        $this
            ->actingAs($admin)
            ->post(
                '/api/v1/bnba/imports',
                [
                    'period_id'
                        => $period->id,

                    'file'
                        => $file,
                ],
                [
                    'Accept'
                        => 'application/json',
                ]
            )
            ->assertCreated()
            ->assertJsonPath(
                'data.status',
                'preview_ready'
            )
            ->assertJsonPath(
                'data.summary.total',
                1
            );
    }

    public function test_import_rejects_workbook_exceeding_maximum_rows(): void
    {
        Storage::fake('local');

        config()->set(
            'sipbpnt.bnba_import.max_rows',
            1
        );

        $admin =
            $this->createAdmin();

        $period =
            $this->createPeriod(
                'BPNT-2026-ROWS'
            );

        /*
         * Maksimum 1 data row,
         * tetapi workbook berisi 2.
         */
        $file =
            $this->makeWorkbook(
                2
            );

        $this
            ->actingAs($admin)
            ->post(
                '/api/v1/bnba/imports',
                [
                    'period_id'
                        => $period->id,

                    'file'
                        => $file,
                ],
                [
                    'Accept'
                        => 'application/json',
                ]
            )
            ->assertUnprocessable();
    }

    public function test_import_rejects_workbook_exceeding_maximum_columns(): void
    {
        Storage::fake('local');

        config()->set(
            'sipbpnt.bnba_import.max_columns',
            33
        );

        $admin =
            $this->createAdmin();

        $period =
            $this->createPeriod(
                'BPNT-2026-COLS'
            );

        /*
         * Header resmi = 33 kolom.
         * Tambahkan 1 kolom sehingga total 34.
         */
        $headers =
            $this->headers();

        $headers[] =
            'KOLOM TAMBAHAN';

        $file =
            $this->makeWorkbook(
                1,
                $headers
            );

        $this
            ->actingAs($admin)
            ->post(
                '/api/v1/bnba/imports',
                [
                    'period_id'
                        => $period->id,

                    'file'
                        => $file,
                ],
                [
                    'Accept'
                        => 'application/json',
                ]
            )
            ->assertUnprocessable();
    }

    public function test_import_rejects_duplicate_mapped_header(): void
    {
        Storage::fake('local');

        $admin =
            $this->createAdmin();

        $period =
            $this->createPeriod(
                'BPNT-2026-DUP-HEADER'
            );

        $headers =
            $this->headers();

        /*
         * NIK sudah terdapat pada kolom B.
         * Menambahkan NIK kedua harus ditolak
         * oleh header mapper.
         */
        $headers[] =
            'NIK';

        $file =
            $this->makeWorkbook(
                1,
                $headers
            );

        $this
            ->actingAs($admin)
            ->post(
                '/api/v1/bnba/imports',
                [
                    'period_id'
                        => $period->id,

                    'file'
                        => $file,
                ],
                [
                    'Accept'
                        => 'application/json',
                ]
            )
            ->assertUnprocessable();
    }

    public function test_import_accepts_period_regardless_of_legacy_active_flag(): void
    {
        Storage::fake('local');

        $admin =
            $this->createAdmin();

        /*
         * is_active adalah field legacy.
         *
         * Nilainya tidak lagi menentukan
         * apakah periode boleh menerima BNBA.
         */
        $period =
            BpntPeriod::query()
                ->create([
                    'code'
                        => 'BPNT-2026-LEGACY',

                    'name'
                        => 'Periode Uji',

                    'year'
                        => 2026,

                    'is_active'
                        => false,
                ]);

        $file =
            $this->makeWorkbook(
                1
            );

        $this
            ->actingAs($admin)
            ->post(
                '/api/v1/bnba/imports',
                [
                    'period_id'
                        => $period->id,

                    'file'
                        => $file,
                ],
                [
                    'Accept'
                        => 'application/json',
                ]
            )
            ->assertCreated()
            ->assertJsonPath(
                'data.status',
                'preview_ready'
            );
    }

    private function createAdmin(): User
    {
        return User::factory()
            ->create([
                'role'
                    => UserRole::ADMIN_DINSOS,

                'is_active'
                    => true,
            ]);
    }

    private function createPeriod(
        string $code = 'BPNT-2026-TEST'
    ): BpntPeriod {
        return BpntPeriod::query()
            ->create([
                'code'
                    => $code,

                'name'
                    => 'Periode Uji 2026',

                'year'
                    => 2026,

                'is_active'
                    => false,
            ]);
    }

    /**
     * @param array<int, string>|null $headers
     */
    private function makeWorkbook(
        int $dataRows = 1,
        ?array $headers = null
    ): UploadedFile {
        $spreadsheet =
            new Spreadsheet();

        $sheet =
            $spreadsheet
                ->getActiveSheet();

        $workbookHeaders =
            $headers
            ?? $this->headers();

        $sheet->fromArray(
            $workbookHeaders,
            null,
            'A1'
        );

        for (
            $index = 1;
            $index <= $dataRows;
            $index++
        ) {
            $excelRow =
                $index + 1;

            $nik =
                sprintf(
                    '1234567891%06d',
                    $index
                );

            $nkk =
                sprintf(
                    '2234567891%06d',
                    $index
                );

            $row = [
                '2017',
                $nik,
                $nkk,
                'KPM UJI '.$index,
                'MOJOKERTO',
                '23/07/1957',
                'IBU UJI',
                'ALAMAT UJI '.$index,
                '001',
                '003',
                'JAGALAN',
                'KRANGGAN',
                '123456789'.$index,
                'E WAROENG UJI',
                'PENGAJUAN 2026',
                '1 PENERIMA',
                'TUB 01',
                'TUB 01',
                'TUB 02',
                'TUB 03',
                'TUB 03',
                'TUB 03',
                null,
                null,
                null,
                null,
                null,
                null,
                'SK 2026',
                '1 PENERIMA',
                'YA PBI JKN JANUARI 2026',
                '3',
                150000,
            ];

            /*
             * Kalau header mempunyai kolom tambahan,
             * tambahkan nilai dummy agar highest column
             * benar-benar terbaca oleh PhpSpreadsheet.
             */
            while (
                count($row)
                <
                count($workbookHeaders)
            ) {
                $row[] =
                    'DATA TAMBAHAN';
            }

            $sheet->fromArray(
                $row,
                null,
                'A'.$excelRow
            );

            $sheet
                ->setCellValueExplicit(
                    'B'.$excelRow,
                    $nik,
                    DataType::TYPE_STRING
                );

            $sheet
                ->setCellValueExplicit(
                    'C'.$excelRow,
                    $nkk,
                    DataType::TYPE_STRING
                );

            $sheet
                ->setCellValueExplicit(
                    'M'.$excelRow,
                    '123456789'.$index,
                    DataType::TYPE_STRING
                );
        }

        $temporaryPath =
            tempnam(
                sys_get_temp_dir(),
                'bnba-hardening-'
            );

        if (
            $temporaryPath === false
        ) {
            $this->fail(
                'Temporary file tidak dapat dibuat.'
            );
        }

        $xlsxPath =
            $temporaryPath
            .'.xlsx';

        @unlink(
            $temporaryPath
        );

        (new Xlsx(
            $spreadsheet
        ))->save(
            $xlsxPath
        );

        $spreadsheet
            ->disconnectWorksheets();

        return new UploadedFile(
            $xlsxPath,
            'bnba-hardening.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
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