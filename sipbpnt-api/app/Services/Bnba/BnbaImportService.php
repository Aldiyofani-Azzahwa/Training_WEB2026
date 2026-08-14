<?php

declare(strict_types=1);

namespace App\Services\Bnba;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\BnbaImportRepositoryInterface;
use App\Contracts\Repositories\BpntParticipantRepositoryInterface;
use App\Contracts\Repositories\BpntPeriodRepositoryInterface;
use App\Contracts\Repositories\KpmRepositoryInterface;
use App\DTOs\Bnba\BnbaRowData;
use App\Enums\BnbaImportStatus;
use App\Enums\BnbaRowStatus;
use App\Models\BnbaImport;
use App\Models\BpntPeriod;
use App\Models\User;
use App\Support\Bnba\BnbaSpreadsheetParser;
use App\Support\Security\SensitiveIdentity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class BnbaImportService
{
    public function __construct(
        private readonly BpntPeriodRepositoryInterface $periods,
        private readonly BnbaImportRepositoryInterface $imports,
        private readonly KpmRepositoryInterface $kpms,
        private readonly BpntParticipantRepositoryInterface $participants,
        private readonly AuditLogRepositoryInterface $auditLogs,
        private readonly BnbaSpreadsheetParser $parser,
        private readonly SensitiveIdentity $identity,
    ) {}

    public function history(
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->imports
            ->paginate($perPage);
    }

    public function upload(
        UploadedFile $file,
        int $periodId,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): BnbaImport {
        $period =
            $this->periods
                ->findOrFail($periodId);

        if (! $period->is_active) {
            throw ValidationException::withMessages([
                'period_id' => [
                    'Periode BPNT tidak aktif '
                    .'dan tidak dapat menerima impor.',
                ],
            ]);
        }

        $realPath =
            $file->getRealPath();

        if ($realPath === false) {
            throw ValidationException::withMessages([
                'file' => [
                    'File upload tidak dapat dibaca.',
                ],
            ]);
        }

        $fileHash =
            hash_file(
                'sha256',
                $realPath
            );

        if ($fileHash === false) {
            throw ValidationException::withMessages([
                'file' => [
                    'Checksum file BNBA '
                    .'tidak dapat dihitung.',
                ],
            ]);
        }

        $parsedRows =
            $this->parser->parse(
                $realPath,
                $period->year
            );

        if ($parsedRows === []) {
            throw ValidationException::withMessages([
                'file' => [
                    'File BNBA tidak memiliki '
                    .'baris data.',
                ],
            ]);
        }

        $preparedRows =
            $this->prepareRows(
                $period,
                $parsedRows
            );

        $extension =
            strtolower(
                $file
                    ->getClientOriginalExtension()
            );

        $storedName =
            Str::uuid()->toString()
            .'.'
            .$extension;

        $directory =
            'bnba-imports/'
            .now()->format('Y/m');

        $storedPath =
            $file->storeAs(
                $directory,
                $storedName,
                'local'
            );

        if ($storedPath === false) {
            throw ValidationException::withMessages([
                'file' => [
                    'File BNBA gagal disimpan '
                    .'ke private storage.',
                ],
            ]);
        }

        try {
            return DB::transaction(
                function () use (
                    $file,
                    $period,
                    $actor,
                    $ipAddress,
                    $userAgent,
                    $storedPath,
                    $preparedRows,
                    $fileHash
                ): BnbaImport {
                    $summary =
                        $this->summary(
                            $preparedRows
                        );

                    $import =
                        $this->imports->create([
                            'bpnt_period_id'
                                => $period->id,

                            'uploaded_by'
                                => $actor->id,

                            'status'
                                => BnbaImportStatus
                                    ::PREVIEW_READY,

                            'original_name'
                                => basename(
                                    $file
                                        ->getClientOriginalName()
                                ),

                            'stored_path'
                                => $storedPath,

                            'file_sha256'
                                => $fileHash,

                            ...$summary,
                        ]);

                    $now = now();

                    $rows =
                        array_map(
                            static function (
                                array $row
                            ) use (
                                $import,
                                $now
                            ): array {
                                return [
                                    'bnba_import_id'
                                        => $import->id,

                                    ...$row,

                                    'created_at'
                                        => $now,

                                    'updated_at'
                                        => $now,
                                ];
                            },
                            $preparedRows
                        );

                    $this->imports
                        ->insertRows(
                            $rows
                        );

                    $this->auditLogs
                        ->record([
                            'user_id'
                                => $actor->id,

                            'action'
                                => 'bnba.import.uploaded',

                            'auditable_type'
                                => BnbaImport::class,

                            'auditable_id'
                                => $import->id,

                            'metadata' => [
                                'period_id'
                                    => $period->id,

                                'total_rows'
                                    => $summary[
                                        'total_rows'
                                    ],

                                'valid_rows'
                                    => $summary[
                                        'valid_rows'
                                    ],

                                'warning_rows'
                                    => $summary[
                                        'warning_rows'
                                    ],

                                'invalid_rows'
                                    => $summary[
                                        'invalid_rows'
                                    ],

                                'duplicate_rows'
                                    => $summary[
                                        'duplicate_rows'
                                    ],
                            ],

                            'ip_address'
                                => $ipAddress,

                            'user_agent'
                                => $userAgent,
                        ]);

                    return $this->imports
                        ->findOrFail(
                            $import->id
                        );
                }
            );
        } catch (Throwable $exception) {
            Storage::disk('local')
                ->delete($storedPath);

            throw $exception;
        }
    }

    /**
     * @return array{
     *     import: BnbaImport,
     *     rows: LengthAwarePaginator
     * }
     */
    public function preview(
        int $importId,
        ?string $status,
        ?string $search,
        int $perPage
    ): array {
        $import =
            $this->imports
                ->findOrFail(
                    $importId
                );

        $normalizedSearch =
            $search !== null
                ? trim($search)
                : null;

        $nikHash = null;

        if (
            $normalizedSearch !== null
            && preg_match(
                '/^\d{16}$/',
                $normalizedSearch
            ) === 1
        ) {
            $nikHash =
                $this->identity->hash(
                    $normalizedSearch
                );
        }

        return [
            'import' => $import,

            'rows'
                => $this->imports
                    ->paginateRows(
                        $import,
                        $status,
                        $normalizedSearch,
                        $nikHash,
                        $perPage
                    ),
        ];
    }

    public function confirm(
        int $importId,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): BnbaImport {
        try {
            return DB::transaction(
                function () use (
                    $importId,
                    $actor,
                    $ipAddress,
                    $userAgent
                ): BnbaImport {
                    $import =
                        $this->imports
                            ->findForUpdate(
                                $importId
                            );

                    if (
                        $import->status
                        !==
                        BnbaImportStatus
                            ::PREVIEW_READY
                    ) {
                        throw ValidationException
                            ::withMessages([
                                'import' => [
                                    'Impor ini sudah diproses '
                                    .'dan tidak dapat '
                                    .'dikonfirmasi ulang.',
                                ],
                            ]);
                    }

                    $period =
                        $this->periods
                            ->findOrFail(
                                $import
                                    ->bpnt_period_id
                            );

                    if (! $period->is_active) {
                        throw ValidationException
                            ::withMessages([
                                'import' => [
                                    'Periode BPNT '
                                    .'sudah tidak aktif.',
                                ],
                            ]);
                    }

                    $rows =
                        $this->imports
                            ->confirmableRows(
                                $import
                            );

                    if ($rows->isEmpty()) {
                        throw ValidationException
                            ::withMessages([
                                'import' => [
                                    'Tidak ada baris valid '
                                    .'atau warning '
                                    .'yang dapat diimpor.',
                                ],
                            ]);
                    }

                    foreach ($rows as $row) {
                        $kpm =
                            $this->kpms
                                ->upsertFromImportRow(
                                    $row
                                );

                        $this->participants
                            ->createFromImportRow(
                                $period,
                                $kpm,
                                $import,
                                $row
                            );
                    }

                    $confirmed =
                        $this->imports
                            ->update(
                                $import,
                                [
                                    'status'
                                        => BnbaImportStatus
                                            ::CONFIRMED,

                                    'confirmed_by'
                                        => $actor->id,

                                    'confirmed_at'
                                        => now(),
                                ]
                            );

                    $this->auditLogs
                        ->record([
                            'user_id'
                                => $actor->id,

                            'action'
                                => 'bnba.import.confirmed',

                            'auditable_type'
                                => BnbaImport::class,

                            'auditable_id'
                                => $confirmed->id,

                            'metadata' => [
                                'period_id'
                                    => $period->id,

                                'imported_rows'
                                    => $rows->count(),

                                'skipped_invalid_rows'
                                    => $import
                                        ->invalid_rows,

                                'skipped_duplicate_rows'
                                    => $import
                                        ->duplicate_rows,
                            ],

                            'ip_address'
                                => $ipAddress,

                            'user_agent'
                                => $userAgent,
                        ]);

                    return $this->imports
                        ->findOrFail(
                            $confirmed->id
                        );
                },
                3
            );
        } catch (
            QueryException $exception
        ) {
            if (
                ($exception->errorInfo[0] ?? null)
                === '23000'
            ) {
                throw ValidationException
                    ::withMessages([
                        'import' => [
                            'Konfirmasi dibatalkan '
                            .'karena terdapat data '
                            .'yang sudah masuk pada '
                            .'periode ini. Muat ulang '
                            .'preview sebelum mencoba '
                            .'lagi.',
                        ],
                    ]);
            }

            throw $exception;
        }
    }

    /**
     * @param array<int, BnbaRowData> $rows
     * @return array<int, array<string, mixed>>
     */
    private function prepareRows(
        BpntPeriod $period,
        array $rows
    ): array {
        $nikHashes = [];

        foreach ($rows as $row) {
            if (
                preg_match(
                    '/^\d{16}$/',
                    $row->nik
                ) === 1
            ) {
                $nikHashes[] =
                    $this->identity
                        ->hash(
                            $row->nik
                        );
            }
        }

        $existingHashes =
            array_flip(
                $this->participants
                    ->existingNikHashesForPeriod(
                        $period->id,
                        $nikHashes
                    )
            );

        $seen = [];
        $prepared = [];

        foreach ($rows as $row) {
            $nikHash =
                preg_match(
                    '/^\d{16}$/',
                    $row->nik
                ) === 1
                    ? $this->identity
                        ->hash($row->nik)
                    : null;

            $nkkHash =
                preg_match(
                    '/^\d{16}$/',
                    $row->nkk
                ) === 1
                    ? $this->identity
                        ->hash($row->nkk)
                    : null;

            $status =
                BnbaRowStatus::VALID;

            $errors =
                $row->errors;

            $warnings =
                $row->warnings;

            if ($row->hasErrors()) {
                $status =
                    BnbaRowStatus::INVALID;
            } elseif (
                $nikHash !== null
                && (
                    isset($seen[$nikHash])
                    || isset(
                        $existingHashes[
                            $nikHash
                        ]
                    )
                )
            ) {
                $status =
                    BnbaRowStatus::DUPLICATE;

                $errors[] =
                    isset(
                        $existingHashes[
                            $nikHash
                        ]
                    )
                        ? 'NIK sudah terdaftar '
                            .'pada periode BPNT '
                            .'yang dipilih.'
                        : 'NIK muncul lebih dari '
                            .'satu kali dalam '
                            .'file yang sama.';
            } elseif (
                $row->hasWarnings()
            ) {
                $status =
                    BnbaRowStatus::WARNING;
            }

            if (
                $nikHash !== null
                && ! $row->hasErrors()
            ) {
                $seen[$nikHash] = true;
            }

            $prepared[] = [
                'row_number'
                    => $row->rowNumber,

                'status'
                    => $status->value,

                'membership_year'
                    => $row->membershipYear !== ''
                        ? $row->membershipYear
                        : null,

                'nik_hash'
                    => $nikHash,

                'nik_ciphertext'
                    => $row->nik !== ''
                        ? $this->identity
                            ->encrypt(
                                $row->nik
                            )
                        : null,

                'nkk_hash'
                    => $nkkHash,

                'nkk_ciphertext'
                    => $row->nkk !== ''
                        ? $this->identity
                            ->encrypt(
                                $row->nkk
                            )
                        : null,

                'full_name'
                    => $row->fullName !== ''
                        ? $row->fullName
                        : null,

                'birth_place'
                    => $row->birthPlace,

                'birth_date'
                    => $row->birthDate
                        ?->format('Y-m-d'),

                'mother_name'
                    => $row->motherName,

                'address'
                    => $row->address !== ''
                        ? $row->address
                        : null,

                'rt'
                    => $row->rt,

                'rw'
                    => $row->rw,

                'kelurahan'
                    => $row->kelurahan !== ''
                        ? $row->kelurahan
                        : null,

                'kecamatan'
                    => $row->kecamatan !== ''
                        ? $row->kecamatan
                        : null,

                'account_ciphertext'
                    => $row->accountNumber !== ''
                        ? $this->identity
                            ->encrypt(
                                $row
                                    ->accountNumber
                            )
                        : null,

                'e_warung_name'
                    => $row->eWarungName !== ''
                        ? $row->eWarungName
                        : null,

                'source_status'
                    => $row->sourceStatus,

                'source_description'
                    => $row
                        ->sourceDescription,

                'monthly_statuses'
                    => json_encode(
                        $row
                            ->monthlyStatuses,
                        JSON_THROW_ON_ERROR
                    ),

                'sk_status'
                    => $row->skStatus,

                'sk_description'
                    => $row->skDescription,

                'apbn_march_status'
                    => $row
                        ->apbnMarchStatus,

                'welfare_rank'
                    => $row->welfareRank,

                'nominal'
                    => $row->nominal,

                'errors'
                    => $errors !== []
                        ? json_encode(
                            array_values(
                                array_unique(
                                    $errors
                                )
                            ),
                            JSON_THROW_ON_ERROR
                        )
                        : null,

                'warnings'
                    => $warnings !== []
                        ? json_encode(
                            array_values(
                                array_unique(
                                    $warnings
                                )
                            ),
                            JSON_THROW_ON_ERROR
                        )
                        : null,
            ];
        }

        return $prepared;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, int>
     */
    private function summary(
        array $rows
    ): array {
        $counts = [
            BnbaRowStatus::VALID->value
                => 0,

            BnbaRowStatus::WARNING->value
                => 0,

            BnbaRowStatus::INVALID->value
                => 0,

            BnbaRowStatus::DUPLICATE->value
                => 0,
        ];

        foreach ($rows as $row) {
            $counts[
                (string)
                $row['status']
            ]++;
        }

        return [
            'total_rows'
                => count($rows),

            'valid_rows'
                => $counts[
                    BnbaRowStatus
                        ::VALID
                        ->value
                ],

            'warning_rows'
                => $counts[
                    BnbaRowStatus
                        ::WARNING
                        ->value
                ],

            'invalid_rows'
                => $counts[
                    BnbaRowStatus
                        ::INVALID
                        ->value
                ],

            'duplicate_rows'
                => $counts[
                    BnbaRowStatus
                        ::DUPLICATE
                        ->value
                ],
        ];
    }
}