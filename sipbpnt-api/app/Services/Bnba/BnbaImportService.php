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
use App\Exceptions\Bnba\BnbaImportIntegrityException;
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
        private readonly BnbaImportFileIntegrityService $fileIntegrity,
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
                ->findOrFail(
                    $periodId
                );

        /*
         * Satu periode hanya memiliki
         * satu BNBA.
         *
         * Kalau salah, Admin harus
         * Hapus BNBA lalu upload ulang.
         */
        if (
            $this->imports
                ->latestForPeriod(
                    $period->id
                )
            !== null
        ) {
            throw ValidationException
                ::withMessages([
                    'period_id' => [
                        'Periode ini sudah memiliki BNBA. Hapus BNBA yang ada terlebih dahulu sebelum melakukan upload ulang.',
                    ],
                ]);
        }

        $realPath =
            $file->getRealPath();

        if ($realPath === false) {
            throw ValidationException
                ::withMessages([
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
            throw ValidationException
                ::withMessages([
                    'file' => [
                        'Checksum file BNBA tidak dapat dihitung.',
                    ],
                ]);
        }

        $parsedRows =
            $this->parser
                ->parse(
                    $realPath,
                    $period->year
                );

        if ($parsedRows === []) {
            throw ValidationException
                ::withMessages([
                    'file' => [
                        'File BNBA tidak memiliki baris data.',
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
            Str::uuid()
                ->toString()
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
            throw ValidationException
                ::withMessages([
                    'file' => [
                        'File BNBA gagal disimpan ke private storage.',
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
                    /*
                     * Cek lagi di dalam transaction.
                     */
                    if (
                        $this->imports
                            ->latestForPeriod(
                                $period->id
                            )
                        !== null
                    ) {
                        throw ValidationException
                            ::withMessages([
                                'period_id' => [
                                    'Periode ini sudah memiliki BNBA.',
                                ],
                            ]);
                    }

                    $summary =
                        $this->summary(
                            $preparedRows
                        );

                    $import =
                        $this->imports
                            ->create([
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
                        ->insertRows($rows);

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
                ->delete(
                    $storedPath
                );

            throw $exception;
        }
    }

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
            &&
            preg_match(
                '/^\d{16}$/',
                $normalizedSearch
            ) === 1
        ) {
            $nikHash =
                $this->identity
                    ->hash(
                        $normalizedSearch
                    );
        }

        return [
            'import'
                => $import,

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
                                    'Import ini sudah diproses dan tidak dapat dikonfirmasi ulang.',
                                ],
                            ]);
                    }

                    $period =
                        $this->periods
                            ->findOrFail(
                                $import
                                    ->bpnt_period_id
                            );

                    /*
                     * Integrity check Tahap 1C
                     * tetap dipertahankan.
                     */
                    $integrity =
                        $this->fileIntegrity
                            ->inspect(
                                $import
                            );

                    if (
                        ! $integrity
                            ->isVerified()
                    ) {
                        throw new
                            BnbaImportIntegrityException(
                                importId:
                                    $import->id,

                                periodId:
                                    $period->id,

                                reason:
                                    $integrity->status,

                                message:
                                    $integrity->message(),
                            );
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
                                    'Tidak ada baris valid atau warning yang dapat dikonfirmasi.',
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

                                'source_file_integrity'
                                    => 'verified',
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
            BnbaImportIntegrityException $exception
        ) {
            $this->auditLogs
                ->record([
                    'user_id'
                        => $actor->id,

                    'action'
                        => 'bnba.import.integrity_failed',

                    'auditable_type'
                        => BnbaImport::class,

                    'auditable_id'
                        => $exception->importId,

                    'metadata' => [
                        'period_id'
                            => $exception->periodId,

                        'reason'
                            => $exception->reason,
                    ],

                    'ip_address'
                        => $ipAddress,

                    'user_agent'
                        => $userAgent,
                ]);

            throw ValidationException
                ::withMessages([
                    'import' => [
                        $exception->getMessage(),
                    ],
                ]);
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
                            'Konfirmasi dibatalkan karena terdapat data yang bentrok pada periode ini.',
                        ],
                    ]);
            }

            throw $exception;
        }
    }

    public function deleteForPeriod(
        int $periodId,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): array {
        $result =
            DB::transaction(
                function () use (
                    $periodId,
                    $actor,
                    $ipAddress,
                    $userAgent
                ): array {
                    $period =
                        $this->periods
                            ->findOrFail(
                                $periodId
                            );

                    $imports =
                        $this->imports
                            ->forPeriodForUpdate(
                                $period->id
                            );

                    if ($imports->isEmpty()) {
                        throw ValidationException
                            ::withMessages([
                                'bnba' => [
                                    'Periode ini belum memiliki data BNBA.',
                                ],
                            ]);
                    }

                    $paths =
                        $imports
                            ->pluck('stored_path')
                            ->filter()
                            ->values()
                            ->all();

                    $importIds =
                        $imports
                            ->pluck('id')
                            ->map(
                                static fn ($id): int =>
                                    (int) $id
                            )
                            ->values()
                            ->all();

                    /*
                     * Participant harus dihapus
                     * sebelum bnba_imports karena FK
                     * participant menggunakan restrict.
                     */
                    $participantsDeleted =
                        $this->participants
                            ->deleteForPeriod(
                                $period->id
                            );

                    /*
                     * bnba_import_rows otomatis
                     * terhapus karena FK cascade.
                     */
                    $importsDeleted =
                        $this->imports
                            ->deleteForPeriod(
                                $period->id
                            );

                    $this->auditLogs
                        ->record([
                            'user_id'
                                => $actor->id,

                            'action'
                                => 'bnba.period_data.deleted',

                            'auditable_type'
                                => BpntPeriod::class,

                            'auditable_id'
                                => $period->id,

                            'metadata' => [
                                'period_name'
                                    => $period->name,

                                'import_ids'
                                    => $importIds,

                                'imports_deleted'
                                    => $importsDeleted,

                                'participants_deleted'
                                    => $participantsDeleted,
                            ],

                            'ip_address'
                                => $ipAddress,

                            'user_agent'
                                => $userAgent,
                        ]);

                    return [
                        'paths'
                            => $paths,

                        'imports_deleted'
                            => $importsDeleted,

                        'participants_deleted'
                            => $participantsDeleted,
                    ];
                }
            );

        /*
         * Setelah DB berhasil commit,
         * file Excel sumber dibersihkan.
         */
        foreach (
            $result['paths']
            as $path
        ) {
            Storage::disk('local')
                ->delete(
                    (string) $path
                );
        }

        return [
            'imports_deleted'
                => $result[
                    'imports_deleted'
                ],

            'participants_deleted'
                => $result[
                    'participants_deleted'
                ],
        ];
    }

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
                        ->hash(
                            $row->nik
                        )
                    : null;

            $nkkHash =
                preg_match(
                    '/^\d{16}$/',
                    $row->nkk
                ) === 1
                    ? $this->identity
                        ->hash(
                            $row->nkk
                        )
                    : null;

            $errors =
                $row->errors;

            $warnings =
                $row->warnings;

            if (
                mb_strlen(
                    $row->membershipYear
                ) > 4
            ) {
                $errors[] =
                    'TAHUN KEPESERTAAN melebihi panjang maksimal 4 karakter.';
            }

            if (
                $row->welfareRank !== null
                &&
                (
                    $row->welfareRank < 0
                    ||
                    $row->welfareRank > 255
                )
            ) {
                $errors[] =
                    'PERINGKAT KESEJAHTERAAN KELUARGA berada di luar batas penyimpanan.';
            }

            $status =
                BnbaRowStatus::VALID;

            if ($errors !== []) {
                $status =
                    BnbaRowStatus::INVALID;
            } elseif (
                $nikHash !== null
                &&
                (
                    isset(
                        $seen[$nikHash]
                    )
                    ||
                    isset(
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
                        ? 'NIK sudah terdaftar pada periode BPNT yang dipilih.'
                        : 'NIK muncul lebih dari satu kali dalam file yang sama.';
            } elseif ($warnings !== []) {
                $status =
                    BnbaRowStatus::WARNING;
            }

            if (
                $nikHash !== null
                &&
                $status !==
                BnbaRowStatus::INVALID
            ) {
                $seen[$nikHash] = true;
            }

            $prepared[] = [
                'row_number'
                    => $row->rowNumber,

                'status'
                    => $status->value,

                'membership_year'
                    => $this
                        ->boundedNullableString(
                            $row->membershipYear,
                            4
                        ),

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
                    => $this
                        ->boundedNullableString(
                            $row->fullName,
                            150
                        ),

                'birth_place'
                    => $this
                        ->boundedNullableString(
                            $row->birthPlace,
                            100
                        ),

                'birth_date'
                    => $row
                        ->birthDate
                        ?->format('Y-m-d'),

                'mother_name'
                    => $this
                        ->boundedNullableString(
                            $row->motherName,
                            150
                        ),

                'address'
                    => $row->address !== ''
                        ? $row->address
                        : null,

                'rt'
                    => $row->rt,

                'rw'
                    => $row->rw,

                'kelurahan'
                    => $this
                        ->boundedNullableString(
                            $row->kelurahan,
                            100
                        ),

                'kecamatan'
                    => $this
                        ->boundedNullableString(
                            $row->kecamatan,
                            100
                        ),

                'account_ciphertext'
                    => $row->accountNumber !== ''
                        ? $this->identity
                            ->encrypt(
                                $row->accountNumber
                            )
                        : null,

                'e_warung_name'
                    => $this
                        ->boundedNullableString(
                            $row->eWarungName,
                            200
                        ),

                'source_status'
                    => $this
                        ->boundedNullableString(
                            $row->sourceStatus,
                            100
                        ),

                'source_description'
                    => $this
                        ->boundedNullableString(
                            $row->sourceDescription,
                            255
                        ),

                'monthly_statuses'
                    => json_encode(
                        $row->monthlyStatuses,
                        JSON_THROW_ON_ERROR
                    ),

                'sk_status'
                    => $this
                        ->boundedNullableString(
                            $row->skStatus,
                            100
                        ),

                'sk_description'
                    => $this
                        ->boundedNullableString(
                            $row->skDescription,
                            255
                        ),

                'apbn_march_status'
                    => $this
                        ->boundedNullableString(
                            $row->apbnMarchStatus,
                            255
                        ),

                'welfare_rank'
                    => (
                        $row->welfareRank !== null
                        &&
                        $row->welfareRank >= 0
                        &&
                        $row->welfareRank <= 255
                    )
                        ? $row->welfareRank
                        : null,

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

    private function boundedNullableString(
        ?string $value,
        int $maxLength
    ): ?string {
        if (
            $value === null
            ||
            $value === ''
        ) {
            return null;
        }

        return mb_substr(
            $value,
            0,
            $maxLength
        );
    }

    private function summary(
        array $rows
    ): array {
        $counts = [
            BnbaRowStatus
                ::VALID
                ->value
                => 0,

            BnbaRowStatus
                ::WARNING
                ->value
                => 0,

            BnbaRowStatus
                ::INVALID
                ->value
                => 0,

            BnbaRowStatus
                ::DUPLICATE
                ->value
                => 0,
        ];

        foreach ($rows as $row) {
            $status =
                (string)
                $row['status'];

            if (
                array_key_exists(
                    $status,
                    $counts
                )
            ) {
                $counts[$status]++;
            }
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