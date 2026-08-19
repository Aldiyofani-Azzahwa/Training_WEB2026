<?php

declare(strict_types=1);

namespace App\Services\Bnba;

use App\Contracts\Repositories\BnbaImportRepositoryInterface;
use App\DTOs\Bnba\BnbaFileIntegrityResult;

final class BnbaImportRetentionAuditService
{
    public function __construct(
        private readonly
        BnbaImportRepositoryInterface $imports,

        private readonly
        BnbaImportFileIntegrityService $integrity,
    ) {}

    /**
     * @return array{
     *     policy: array{
     *         raw_file: string,
     *         staging_rows: string
     *     },
     *     summary: array<string, int>,
     *     imports: array<int, array<string, mixed>>
     * }
     */
    public function audit(
        int $limit = 500
    ): array {
        $limit = min(
            max(
                $limit,
                1
            ),
            5000
        );

        $imports =
            $this->imports
                ->latestForRetentionAudit(
                    $limit
                );

        $summary = [
            'scanned_imports'
                => 0,

            'staging_rows'
                => 0,

            'files_verified'
                => 0,

            'files_missing'
                => 0,

            'files_unreadable'
                => 0,

            'files_checksum_mismatch'
                => 0,
        ];

        $items = [];

        foreach ($imports as $import) {
            $result =
                $this->integrity
                    ->inspect(
                        $import
                    );

            $summary[
                'scanned_imports'
            ]++;

            $summary[
                'staging_rows'
            ] +=
                (int)
                $import->rows_count;

            match ($result->status) {
                BnbaFileIntegrityResult
                    ::VERIFIED =>
                    $summary[
                        'files_verified'
                    ]++,

                BnbaFileIntegrityResult
                    ::MISSING =>
                    $summary[
                        'files_missing'
                    ]++,

                BnbaFileIntegrityResult
                    ::UNREADABLE =>
                    $summary[
                        'files_unreadable'
                    ]++,

                BnbaFileIntegrityResult
                    ::CHECKSUM_MISMATCH =>
                    $summary[
                        'files_checksum_mismatch'
                    ]++,

                default => null,
            };

            $items[] = [
                'id'
                    => $import->id,

                'period_code'
                    => $import
                        ->period
                        ?->code,

                'status'
                    => $import
                        ->status
                        ->value,

                'created_at'
                    => $import
                        ->created_at
                        ?->toIso8601String(),

                'confirmed_at'
                    => $import
                        ->confirmed_at
                        ?->toIso8601String(),

                'staging_rows'
                    => (int)
                        $import
                            ->rows_count,

                'file_integrity'
                    => $result->status,
            ];
        }

        return [
            'policy' => [
                'raw_file'
                    => (string) config(
                        'sipbpnt.bnba_import.retention.raw_file',
                        'retain_until_policy_approved'
                    ),

                'staging_rows'
                    => (string) config(
                        'sipbpnt.bnba_import.retention.staging_rows',
                        'retain_until_policy_approved'
                    ),
            ],

            'summary'
                => $summary,

            'imports'
                => $items,
        ];
    }
}