<?php

declare(strict_types=1);

namespace Tests\Feature\Bnba;

use App\Enums\BnbaImportStatus;
use App\Enums\UserRole;
use App\Models\BnbaImport;
use App\Models\BnbaImportRow;
use App\Models\BpntParticipant;
use App\Models\BpntPeriod;
use App\Models\Kpm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;
use Database\Seeders\WilayahSeeder;


class BnbaImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'sipbpnt.identity_hash_key',
            'testing-identity-hash-key-32-bytes-minimum'
        );
        $this->seed(
            WilayahSeeder::class
        );
    }

    public function test_only_admin_can_access_bnba_import_endpoint(): void
    {
        $surveyor =
            User::factory()->create([
                'role'
                => UserRole::SURVEYOR,

                'is_active'
                => true,
            ]);

        $this->actingAs($surveyor)
            ->getJson(
                '/api/v1/bnba/imports'
            )
            ->assertForbidden();
    }

    public function test_admin_can_upload_preview_and_confirm_bnba(): void
    {
        Storage::fake('local');

        $admin =
            User::factory()->create([
                'role'
                => UserRole::ADMIN_DINSOS,

                'is_active'
                => true,
            ]);

        $period =
            BpntPeriod::query()->create([
                'code'
                => 'BPNT-2026-01',

                'name'
                => 'Periode Uji 2026',

                'year'
                => 2026,

                'is_active'
                => true,
            ]);

        $file =
            $this->makeBnbaFile();

        $upload =
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
                );

        $upload
            ->assertCreated()
            ->assertJsonPath(
                'data.status',
                'preview_ready'
            )
            ->assertJsonPath(
                'data.summary.total',
                2
            )
            ->assertJsonPath(
                'data.summary.valid',
                1
            )
            ->assertJsonPath(
                'data.summary.warning',
                1
            )
            ->assertJsonPath(
                'data.summary.invalid',
                0
            )
            ->assertJsonPath(
                'data.summary.duplicate',
                0
            );

        $importId =
            (int) 
            $upload->json(
                'data.id'
            );

        $preview =
            $this
                ->actingAs($admin)
                ->getJson(
                    "/api/v1/bnba/imports/"
                    . "{$importId}/preview"
                );

        $preview
            ->assertOk()
            ->assertJsonCount(
                2,
                'data.rows'
            )
            ->assertJsonPath(
                'data.rows.0.nominal',
                150000
            )
            ->assertJsonPath(
                'data.rows.1.nominal',
                450000
            );

        $maskedNik =
            (string) 
            $preview->json(
                'data.rows.0.nik'
            );

        $this
            ->assertStringContainsString(
                '********',
                $maskedNik
            );

        $this->assertNotSame(
            '1234567891011132',
            $maskedNik
        );

        $confirm =
            $this
                ->actingAs($admin)
                ->postJson(
                    "/api/v1/bnba/imports/"
                    . "{$importId}/confirm"
                );

        $confirm
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                BnbaImportStatus
                ::CONFIRMED
                    ->value
            );

        $this->assertDatabaseCount(
            'kpms',
            2
        );

        $this->assertDatabaseCount(
            'bpnt_participants',
            2
        );

        $this->assertDatabaseHas(
            'bpnt_participants',
            [
                'bpnt_period_id'
                => $period->id,

                'entitlement_amount'
                => 450000,
            ]
        );

        $this->assertSame(
            2,
            Kpm::query()->count()
        );

        $this->assertSame(
            2,
            BpntParticipant::query()
                ->count()
        );
    }

    public function test_duplicate_nik_in_same_file_is_marked_duplicate(): void
    {
        Storage::fake('local');

        $admin =
            User::factory()->create([
                'role'
                => UserRole::ADMIN_DINSOS,

                'is_active'
                => true,
            ]);

        $period =
            BpntPeriod::query()->create([
                'code'
                => 'BPNT-2026-DUP',

                'name'
                => 'Periode Duplicate',

                'year'
                => 2026,

                'is_active'
                => true,
            ]);

        $file =
            $this->makeBnbaFile(
                duplicateSecondNik: true
            );

        $response =
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
                );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.summary.duplicate',
                1
            );

        $import =
            BnbaImport::query()
                ->firstOrFail();

        $this->assertSame(
            1,
            BnbaImportRow::query()
                ->where(
                    'bnba_import_id',
                    $import->id
                )
                ->where(
                    'status',
                    'duplicate'
                )
                ->count()
        );
    }

    public function test_database_rejects_second_bnba_import_for_same_period(): void
    {
        $admin =
            User::factory()
                ->create([
                    'role'
                    => UserRole
                            ::ADMIN_DINSOS,

                    'is_active'
                    => true,
                ]);

        $period =
            BpntPeriod::query()
                ->create([
                    'code'
                    => 'BPNT-2026-UNIQUE',

                    'name'
                    => 'Periode Unique BNBA',

                    'year'
                    => 2026,

                    /*
                     * Field legacy database
                     * masih ada sementara.
                     *
                     * Ini bukan business rule
                     * active period.
                     */
                    'is_active'
                    => false,
                ]);

        BnbaImport::query()
            ->create([
                'bpnt_period_id'
                => $period->id,

                'uploaded_by'
                => $admin->id,

                'status'
                => BnbaImportStatus
                        ::PREVIEW_READY,

                'original_name'
                => 'bnba-pertama.xlsx',

                'stored_path'
                => 'bnba-imports/test/bnba-pertama.xlsx',

                'file_sha256'
                => str_repeat(
                        'a',
                        64
                    ),
            ]);

        /*
         * BNBA kedua dengan period_id
         * yang sama wajib ditolak DB.
         */
        $this->expectException(
            QueryException::class
        );

        BnbaImport::query()
            ->create([
                'bpnt_period_id'
                => $period->id,

                'uploaded_by'
                => $admin->id,

                'status'
                => BnbaImportStatus
                        ::PREVIEW_READY,

                'original_name'
                => 'bnba-kedua.xlsx',

                'stored_path'
                => 'bnba-imports/test/bnba-kedua.xlsx',

                'file_sha256'
                => str_repeat(
                        'b',
                        64
                    ),
            ]);
    }

    public function test_admin_cannot_upload_second_bnba_before_deleting_existing_bnba(): void
    {
        Storage::fake('local');

        $admin =
            User::factory()
                ->create([
                    'role'
                    => UserRole
                            ::ADMIN_DINSOS,

                    'is_active'
                    => true,
                ]);

        $period =
            BpntPeriod::query()
                ->create([
                    'code'
                    => 'BPNT-2026-SECOND',

                    'name'
                    => 'Periode Upload Kedua',

                    'year'
                    => 2026,
                ]);

        /*
         * Upload BNBA pertama.
         */
        $firstUpload =
            $this
                ->actingAs($admin)
                ->post(
                    '/api/v1/bnba/imports',
                    [
                        'period_id'
                        => $period->id,

                        'file'
                        => $this
                                ->makeBnbaFile(),
                    ],
                    [
                        'Accept'
                        => 'application/json',
                    ]
                );

        $firstUpload
            ->assertCreated()
            ->assertJsonPath(
                'data.status',
                BnbaImportStatus
                ::PREVIEW_READY
                    ->value
            );

        /*
         * Tanpa delete BNBA,
         * upload kedua wajib ditolak.
         */
        $secondUpload =
            $this
                ->actingAs($admin)
                ->post(
                    '/api/v1/bnba/imports',
                    [
                        'period_id'
                        => $period->id,

                        'file'
                        => $this
                                ->makeBnbaFile(),
                    ],
                    [
                        'Accept'
                        => 'application/json',
                    ]
                );

        $secondUpload
            ->assertStatus(422)
            ->assertJsonPath(
                'errors.period_id.0',
                'Periode ini sudah memiliki BNBA. Hapus BNBA yang ada terlebih dahulu sebelum melakukan upload ulang.'
            );

        $this->assertSame(
            1,
            BnbaImport::query()
                ->where(
                    'bpnt_period_id',
                    $period->id
                )
                ->count()
        );
    }

    public function test_admin_can_delete_confirmed_bnba_and_upload_new_bnba_again(): void
    {
        Storage::fake('local');

        $admin =
            User::factory()
                ->create([
                    'role'
                    => UserRole
                            ::ADMIN_DINSOS,

                    'is_active'
                    => true,
                ]);

        $period =
            BpntPeriod::query()
                ->create([
                    'code'
                    => 'BPNT-2026-REUPLOAD',

                    'name'
                    => 'Periode Reupload',

                    'year'
                    => 2026,
                ]);

        /*
         * Upload pertama.
         */
        $upload =
            $this
                ->actingAs($admin)
                ->post(
                    '/api/v1/bnba/imports',
                    [
                        'period_id'
                        => $period->id,

                        'file'
                        => $this
                                ->makeBnbaFile(),
                    ],
                    [
                        'Accept'
                        => 'application/json',
                    ]
                );

        $upload
            ->assertCreated();

        $importId =
            (int) 
            $upload->json(
                'data.id'
            );

        /*
         * Konfirmasi agar participant
         * benar-benar terbentuk.
         */
        $this
            ->actingAs($admin)
            ->postJson(
                "/api/v1/bnba/imports/{$importId}/confirm"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                BnbaImportStatus
                ::CONFIRMED
                    ->value
            );

        $import =
            BnbaImport::query()
                ->findOrFail(
                    $importId
                );

        $storedPath =
            $import->stored_path;

        Storage::disk('local')
            ->assertExists(
                $storedPath
            );

        $this->assertSame(
            2,
            BpntParticipant::query()
                ->where(
                    'bpnt_period_id',
                    $period->id
                )
                ->count()
        );

        /*
         * Hapus BNBA.
         */
        $delete =
            $this
                ->actingAs($admin)
                ->deleteJson(
                    "/api/v1/bpnt-periods/{$period->id}/bnba"
                );

        $delete
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Data BNBA berhasil dihapus.'
            )
            ->assertJsonPath(
                'data.imports_deleted',
                1
            )
            ->assertJsonPath(
                'data.participants_deleted',
                2
            );

        /*
         * Import dan participant periode
         * harus benar-benar kosong.
         */
        $this->assertSame(
            0,
            BnbaImport::query()
                ->where(
                    'bpnt_period_id',
                    $period->id
                )
                ->count()
        );

        $this->assertSame(
            0,
            BpntParticipant::query()
                ->where(
                    'bpnt_period_id',
                    $period->id
                )
                ->count()
        );

        /*
         * Source Excel lama ikut dibersihkan.
         */
        Storage::disk('local')
            ->assertMissing(
                $storedPath
            );

        /*
         * Periode harus kembali
         * dianggap kosong.
         */
        $periodResponse =
            $this
                ->actingAs($admin)
                ->getJson(
                    '/api/v1/bpnt-periods'
                );

        $periodResponse
            ->assertOk()
            ->assertJsonPath(
                'data.0.imports_count',
                0
            )
            ->assertJsonPath(
                'data.0.participants_count',
                0
            )
            ->assertJsonPath(
                'data.0.can_delete',
                true
            )
            ->assertJsonPath(
                'data.0.can_edit_year',
                true
            )
            ->assertJsonPath(
                'data.0.bnba',
                null
            );

        /*
         * Setelah BNBA lama dihapus,
         * upload baru wajib bisa dilakukan.
         */
        $replacementUpload =
            $this
                ->actingAs($admin)
                ->post(
                    '/api/v1/bnba/imports',
                    [
                        'period_id'
                        => $period->id,

                        'file'
                        => $this
                                ->makeBnbaFile(),
                    ],
                    [
                        'Accept'
                        => 'application/json',
                    ]
                );

        $replacementUpload
            ->assertCreated()
            ->assertJsonPath(
                'data.status',
                BnbaImportStatus
                ::PREVIEW_READY
                    ->value
            );

        $this->assertSame(
            1,
            BnbaImport::query()
                ->where(
                    'bpnt_period_id',
                    $period->id
                )
                ->count()
        );
    }

    private function makeBnbaFile(
        bool $duplicateSecondNik = false
    ): UploadedFile {
        $headers = [
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

        $spreadsheet =
            new Spreadsheet();

        $sheet =
            $spreadsheet
                ->getActiveSheet();

        $sheet->fromArray(
            $headers,
            null,
            'A1'
        );

        $rows = [
            [
                '2017',
                '1234567891011132',
                '1987654321010123',
                'ADI SUROTO',
                'MOJOKERTO',
                '23/07/1957',
                'SUMARIYAH',
                'ALAMAT UJI 1',
                '001',
                '003',
                'JAGALAN',
                'KRANGGAN',
                '1234566742',
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
            ],
            [
                '2022',

                $duplicateSecondNik
                ? '1234567891011132'
                : '1234567891011136',

                '2987654321010124',
                'ALI SOEFI',
                null,
                '26/12/1985',
                'RUKAYAH',
                'ALAMAT UJI 2',
                null,
                null,
                'JAGALAN',
                'KRANGGAN',
                '1234566743',
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
                null,
                '2',
                '=150000*3',
            ],
        ];

        foreach (
            $rows
            as $rowIndex => $row
        ) {
            $excelRow =
                $rowIndex + 2;

            $sheet->fromArray(
                $row,
                null,
                'A' . $excelRow
            );

            /*
             * NIK harus disimpan sebagai text.
             *
             * NIK memiliki 16 digit sehingga tidak
             * boleh diperlakukan sebagai numeric Excel
             * karena berisiko kehilangan presisi.
             */
            $sheet
                ->setCellValueExplicit(
                    'B' . $excelRow,
                    (string) $row[1],
                    DataType::TYPE_STRING
                );

            /*
             * NKK harus disimpan sebagai text.
             *
             * Sama seperti NIK, nilai 16 digit tidak
             * aman apabila disimpan sebagai angka Excel.
             */
            $sheet
                ->setCellValueExplicit(
                    'C' . $excelRow,
                    (string) $row[2],
                    DataType::TYPE_STRING
                );

            /*
             * Nomor rekening juga direpresentasikan
             * sebagai text pada fixture happy-path.
             *
             * Tujuannya:
             * - menjaga leading zero;
             * - menghindari konversi numeric Excel;
             * - menghindari kehilangan precision;
             * - memastikan row valid tidak berubah
             *   menjadi warning hanya karena tipe cell.
             */
            $sheet
                ->setCellValueExplicit(
                    'M' . $excelRow,
                    (string) $row[12],
                    DataType::TYPE_STRING
                );
        }

        $path =
            tempnam(
                sys_get_temp_dir(),
                'bnba-test-'
            );

        $xlsxPath =
            $path . '.xlsx';

        @unlink($path);

        (new Xlsx($spreadsheet))
            ->save($xlsxPath);

        $spreadsheet
            ->disconnectWorksheets();

        return new UploadedFile(
            $xlsxPath,
            'bnba-test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }
}