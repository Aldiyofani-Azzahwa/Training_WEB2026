<?php

declare(strict_types=1);

namespace Tests\Feature\Bnba;

use App\Enums\BnbaImportStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\BnbaImport;
use App\Models\BnbaImportRow;
use App\Models\BpntParticipant;
use App\Models\BpntPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;
use Database\Seeders\WilayahSeeder;

class BnbaImportRetentionAuditTest extends TestCase
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
            1
        );

        config()->set(
            'sipbpnt.bnba_import.retention.raw_file',
            'retain_until_policy_approved'
        );

        config()->set(
            'sipbpnt.bnba_import.retention.staging_rows',
            'retain_until_policy_approved'
        );
        $this->seed(
            WilayahSeeder::class
        );
    }

    public function test_anonymized_official_layout_import_retains_source_and_staging_after_confirmation(
    ): void {
        Storage::fake(
            'local'
        );

        [
            $admin,
            $period,
        ] = $this
                ->adminAndPeriod();

        $upload =
            $this
                ->actingAs(
                    $admin
                )
                ->post(
                    '/api/v1/bnba/imports',
                    [
                        'period_id'
                        => $period->id,

                        'file'
                        => $this
                                ->makeAnonymizedWorkbook(),
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
                BnbaImportStatus
                ::PREVIEW_READY
                    ->value
            )
            ->assertJsonPath(
                'data.summary.total',
                2
            );

        $import =
            BnbaImport::query()
                ->firstOrFail();

        $this->assertTrue(
            Storage::disk(
                'local'
            )->exists(
                    $import
                        ->stored_path
                )
        );

        $this->assertSame(
            2,
            BnbaImportRow::query()
                ->where(
                    'bnba_import_id',
                    $import->id
                )
                ->count()
        );

        $confirm =
            $this
                ->actingAs(
                    $admin
                )
                ->postJson(
                    '/api/v1/bnba/imports/'
                    . $import->id
                    . '/confirm'
                );

        $confirm
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                BnbaImportStatus
                ::CONFIRMED
                    ->value
            );

        $this->assertTrue(
            Storage::disk(
                'local'
            )->exists(
                    $import
                        ->stored_path
                )
        );

        $this->assertSame(
            2,
            BnbaImportRow::query()
                ->where(
                    'bnba_import_id',
                    $import->id
                )
                ->count()
        );

        $this->assertSame(
            2,
            BpntParticipant::query()
                ->where(
                    'bnba_import_id',
                    $import->id
                )
                ->count()
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'action'
                => 'bnba.import.uploaded',

                'auditable_id'
                => $import->id,
            ]
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'action'
                => 'bnba.import.confirmed',

                'auditable_id'
                => $import->id,
            ]
        );

        $auditMetadata =
            AuditLog::query()
                ->where(
                    'auditable_id',
                    $import->id
                )
                ->get()
                ->map(
                    static fn(
                    AuditLog $log
                ): string =>
                    json_encode(
                        $log->metadata,
                        JSON_THROW_ON_ERROR
                    )
                )
                ->implode(
                    "\n"
                );

        $this->assertStringNotContainsString(
            '3516010101010001',
            $auditMetadata
        );

        $this->assertStringNotContainsString(
            '3516010101019999',
            $auditMetadata
        );

        $this->assertStringNotContainsString(
            'stored_path',
            $auditMetadata
        );
    }

    public function test_confirmation_is_blocked_when_source_file_is_missing_and_failure_is_audited(
    ): void {
        Storage::fake(
            'local'
        );

        [
            $admin,
            $period,
        ] = $this
                ->adminAndPeriod();

        $import =
            $this->uploadImport(
                $admin,
                $period
            );

        Storage::disk(
            'local'
        )->delete(
                $import
                    ->stored_path
            );

        $this
            ->actingAs(
                $admin
            )
            ->postJson(
                '/api/v1/bnba/imports/'
                . $import->id
                . '/confirm'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'import',
            ]);

        $this->assertDatabaseCount(
            'bpnt_participants',
            0
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'action'
                => 'bnba.import.integrity_failed',

                'auditable_id'
                => $import->id,
            ]
        );

        $audit =
            AuditLog::query()
                ->where(
                    'action',
                    'bnba.import.integrity_failed'
                )
                ->where(
                    'auditable_id',
                    $import->id
                )
                ->latest(
                    'id'
                )
                ->firstOrFail();

        $this->assertSame(
            'missing',
            $audit
                ->metadata[
                'reason'
            ]
        );
    }

    public function test_confirmation_is_blocked_when_source_file_checksum_changes(
    ): void {
        Storage::fake(
            'local'
        );

        [
            $admin,
            $period,
        ] = $this
                ->adminAndPeriod();

        $import =
            $this->uploadImport(
                $admin,
                $period
            );

        Storage::disk(
            'local'
        )->put(
                $import->stored_path,
                'tampered-file-content'
            );

        $this
            ->actingAs(
                $admin
            )
            ->postJson(
                '/api/v1/bnba/imports/'
                . $import->id
                . '/confirm'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'import',
            ]);

        $this->assertDatabaseCount(
            'bpnt_participants',
            0
        );

        $audit =
            AuditLog::query()
                ->where(
                    'action',
                    'bnba.import.integrity_failed'
                )
                ->where(
                    'auditable_id',
                    $import->id
                )
                ->latest(
                    'id'
                )
                ->firstOrFail();

        $this->assertSame(
            'checksum_mismatch',
            $audit
                ->metadata[
                'reason'
            ]
        );
    }

    public function test_retention_audit_command_is_read_only(
    ): void {
        Storage::fake(
            'local'
        );

        [
            $admin,
            $period,
        ] = $this
                ->adminAndPeriod();

        $import =
            $this->uploadImport(
                $admin,
                $period
            );

        $beforeImports =
            BnbaImport::query()
                ->count();

        $beforeRows =
            BnbaImportRow::query()
                ->count();

        $exitCode =
            Artisan::call(
                'sipbpnt:bnba-retention-audit',
                [
                    '--limit'
                    => 100,
                ]
            );

        $this->assertSame(
            0,
            $exitCode
        );

        $this->assertSame(
            $beforeImports,
            BnbaImport::query()
                ->count()
        );

        $this->assertSame(
            $beforeRows,
            BnbaImportRow::query()
                ->count()
        );

        $this->assertTrue(
            Storage::disk(
                'local'
            )->exists(
                    $import
                        ->stored_path
                )
        );

        $output =
            Artisan::output();

        $this
            ->assertStringContainsString(
                'retain_until_policy_approved',
                $output
            );

        $this
            ->assertStringContainsString(
                'verified',
                $output
            );
    }

    /**
     * @return array{0: User, 1: BpntPeriod}
     */
    private function adminAndPeriod(
    ): array {
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
                    => 'BPNT-2026-ANON',

                    'name'
                    => 'Periode Uji Anonim 2026',

                    'year'
                    => 2026,

                    'is_active'
                    => true,
                ]);

        return [
            $admin,
            $period,
        ];
    }

    private function uploadImport(
        User $admin,
        BpntPeriod $period
    ): BnbaImport {
        $response =
            $this
                ->actingAs(
                    $admin
                )
                ->post(
                    '/api/v1/bnba/imports',
                    [
                        'period_id'
                        => $period->id,

                        'file'
                        => $this
                                ->makeAnonymizedWorkbook(),
                    ],
                    [
                        'Accept'
                        => 'application/json',
                    ]
                );

        $response
            ->assertCreated();

        return BnbaImport::query()
            ->findOrFail(
                (int) 
                $response
                    ->json(
                        'data.id'
                    )
            );
    }

    private function makeAnonymizedWorkbook(
    ): UploadedFile {
        $spreadsheet =
            new Spreadsheet();

        $sheet =
            $spreadsheet
                ->getActiveSheet();

        $sheet->setTitle(
            'BNBA'
        );

        $sheet->fromArray(
            $this->headers(),
            null,
            'A1'
        );

        $rows = [
            [
                '2017',
                '3516010101010001',
                '3516010101019999',
                'KPM ANONIM SATU',
                'MOJOKERTO',
                '12/05/1965',
                'IBU ANONIM SATU',
                'ALAMAT ANONIM 001',
                '001',
                '002',
                'SURODINAWAN',
                'PRAJURITKULON',
                '000123456789',
                'E-WAROENG ANGGREK SURODINAWAN',
                'PENGAJUAN 2026',
                '1 PENERIMA',
                'TUB 01',
                'TUB 01',
                'TUB 01',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                'SK 2026',
                '1 PENERIMA',
                'YA PBI JKN JANUARI 2026',
                '2',
                150000,
            ],

            [
                '2022',
                '3516010101010002',
                '3516010101019998',
                'KPM ANONIM DUA',
                'MOJOKERTO',
                '20/09/1971',
                'IBU ANONIM DUA',
                'ALAMAT ANONIM 002',
                '003',
                '004',
                'WATES',
                'MAGERSARI',
                '000123456790',
                'E-WAROENG BANCANG SEJAHTERA WATES',
                'PENGAJUAN 2026',
                '1 PENERIMA',
                'TUB 01',
                'TUB 01',
                'TUB 02',
                null,
                null,
                null,
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
                300000,
            ],
        ];

        foreach (
            $rows
            as $index => $row
        ) {
            $excelRow =
                $index + 2;

            $sheet->fromArray(
                $row,
                null,
                'A' . $excelRow
            );

            $sheet
                ->setCellValueExplicit(
                    'B' . $excelRow,
                    (string) 
                    $row[1],
                    DataType::TYPE_STRING
                );

            $sheet
                ->setCellValueExplicit(
                    'C' . $excelRow,
                    (string) 
                    $row[2],
                    DataType::TYPE_STRING
                );

            $sheet
                ->setCellValueExplicit(
                    'M' . $excelRow,
                    (string) 
                    $row[12],
                    DataType::TYPE_STRING
                );
        }

        $temporary =
            tempnam(
                sys_get_temp_dir(),
                'bnba-anonymized-'
            );

        if (
            $temporary === false
        ) {
            self::fail(
                'Tidak dapat membuat '
                . 'temporary file.'
            );
        }

        @unlink(
            $temporary
        );

        $path =
            $temporary
            . '.xlsx';

        (
            new Xlsx(
                $spreadsheet
            )
        )->save(
                $path
            );

        $spreadsheet
            ->disconnectWorksheets();

        return new UploadedFile(
            $path,
            'bnba-anonymized-official-layout.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    /**
     * @return array<int, string>
     */
    private function headers(
    ): array {
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