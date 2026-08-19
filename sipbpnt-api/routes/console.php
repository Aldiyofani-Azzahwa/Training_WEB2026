<?php

declare(strict_types=1);

use App\Services\Bnba\BnbaImportRetentionAuditService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command(
    'inspire',
    function (): void {
        $this->comment(
            Inspiring::quote()
        );
    }
)->purpose(
    'Display an inspiring quote'
);

Artisan::command(
    'sipbpnt:bnba-retention-audit {--limit=500}',
    function (): int {
        $limit =
            (int)
            $this->option(
                'limit'
            );

        if ($limit < 1) {
            $this->error(
                'Option --limit harus minimal 1.'
            );

            return 1;
        }

        /**
         * @var BnbaImportRetentionAuditService $service
         */
        $service = app(
            BnbaImportRetentionAuditService::class
        );

        $report =
            $service->audit(
                $limit
            );

        $this->info(
            'SIPBPNT BNBA Retention Audit'
        );

        $this->line(
            'Raw file policy: '
            .$report[
                'policy'
            ][
                'raw_file'
            ]
        );

        $this->line(
            'Staging rows policy: '
            .$report[
                'policy'
            ][
                'staging_rows'
            ]
        );

        $this->newLine();

        $this->table(
            [
                'Metric',
                'Value',
            ],
            collect(
                $report[
                    'summary'
                ]
            )->map(
                static fn (
                    int $value,
                    string $key
                ): array => [
                    $key,
                    $value,
                ]
            )->values()
                ->all()
        );

        if (
            $report[
                'imports'
            ] !== []
        ) {
            $this->newLine();

            $this->table(
                [
                    'ID',
                    'Period',
                    'Status',
                    'Staging Rows',
                    'File Integrity',
                ],
                array_map(
                    static fn (
                        array $import
                    ): array => [
                        $import[
                            'id'
                        ],

                        $import[
                            'period_code'
                        ],

                        $import[
                            'status'
                        ],

                        $import[
                            'staging_rows'
                        ],

                        $import[
                            'file_integrity'
                        ],
                    ],
                    $report[
                        'imports'
                    ]
                )
            );
        }

        $hasIntegrityProblem =
            $report[
                'summary'
            ][
                'files_missing'
            ] > 0
            ||
            $report[
                'summary'
            ][
                'files_unreadable'
            ] > 0
            ||
            $report[
                'summary'
            ][
                'files_checksum_mismatch'
            ] > 0;

        if ($hasIntegrityProblem) {
            $this->warn(
                'Ditemukan masalah integritas file BNBA. '
                .'Command ini tidak menghapus atau '
                .'memperbaiki file.'
            );

            return 2;
        }

        $this->info(
            'Tidak ditemukan masalah integritas '
            .'pada import yang diperiksa.'
        );

        return 0;
    }
)->purpose(
    'Audit read-only retensi dan integritas '
    .'file import BNBA.'
);