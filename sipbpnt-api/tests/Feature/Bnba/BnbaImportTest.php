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
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

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
                    ."{$importId}/preview"
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
                    ."{$importId}/confirm"
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
                'A'.$excelRow
            );

            $sheet
                ->setCellValueExplicit(
                    'B'.$excelRow,
                    (string) $row[1],
                    DataType::TYPE_STRING
                );

            $sheet
                ->setCellValueExplicit(
                    'C'.$excelRow,
                    (string) $row[2],
                    DataType::TYPE_STRING
                );
        }

        $path =
            tempnam(
                sys_get_temp_dir(),
                'bnba-test-'
            );

        $xlsxPath =
            $path.'.xlsx';

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