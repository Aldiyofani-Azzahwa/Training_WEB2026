<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TABLE =
        'surveyor_assignments';

    private const OLD_KELURAHAN_UNIQUE =
        'surveyor_assignment_period_kelurahan_unique';

    private const NEW_KELURAHAN_INDEX =
        'surveyor_assignment_period_kelurahan_index';

    private const NEW_SURVEYOR_UNIQUE =
        'surveyor_assignment_period_surveyor_unique';

    public function up(): void
    {
        /*
         * Sebelum membuat:
         *
         * UNIQUE(period_id, surveyor_id)
         *
         * bersihkan kemungkinan data lama yang
         * menempatkan Surveyor yang sama di lebih
         * dari satu kelurahan pada periode sama.
         */
        $this->removeDuplicateSurveyorAssignments();

        /*
         * RULE LAMA:
         *
         * UNIQUE(period_id, kelurahan_id)
         *
         * Rule ini harus dilepas karena sekarang
         * satu kelurahan boleh memiliki maksimal
         * tiga Surveyor.
         */
        $this->dropIndexIfExists(
            self::OLD_KELURAHAN_UNIQUE
        );

        /*
         * period_id + kelurahan_id tetap kita
         * pertahankan sebagai INDEX biasa.
         *
         * Ini berguna untuk query jumlah Surveyor
         * dalam satu kelurahan.
         */
        $this->createIndexIfMissing(
            self::NEW_KELURAHAN_INDEX,
            [
                'period_id',
                'kelurahan_id',
            ]
        );

        /*
         * RULE BARU DATABASE:
         *
         * 1 Surveyor hanya boleh memiliki
         * 1 assignment dalam periode yang sama.
         */
        $this->createUniqueIndexIfMissing(
            self::NEW_SURVEYOR_UNIQUE,
            [
                'period_id',
                'surveyor_id',
            ]
        );

        /*
         * PENTING:
         *
         * Kita TIDAK menghapus:
         *
         * surveyor_assignment_period_surveyor_index
         *
         * karena MySQL sebelumnya memberi error
         * 1553 bahwa index tersebut sedang dipakai
         * untuk foreign key.
         *
         * Membiarkannya tidak merusak rule baru.
         */
    }

    public function down(): void
    {
        /*
         * Sebelum mengembalikan rule lama:
         *
         * UNIQUE(period_id, kelurahan_id)
         *
         * pastikan tidak ada kelurahan yang
         * sudah memiliki >1 Surveyor.
         */
        $duplicateKelurahanExists =
            DB::table(
                self::TABLE
            )
                ->select([
                    'period_id',
                    'kelurahan_id',
                ])
                ->groupBy([
                    'period_id',
                    'kelurahan_id',
                ])
                ->havingRaw(
                    'COUNT(*) > 1'
                )
                ->exists();

        if (
            $duplicateKelurahanExists
        ) {
            throw new RuntimeException(
                'Rollback tidak aman karena sudah ada kelurahan yang memiliki lebih dari satu Surveyor.'
            );
        }

        /*
         * Hapus database guard rule baru.
         */
        $this->dropIndexIfExists(
            self::NEW_SURVEYOR_UNIQUE
        );

        /*
         * Hapus index biasa period + kelurahan.
         */
        $this->dropIndexIfExists(
            self::NEW_KELURAHAN_INDEX
        );

        /*
         * Kembalikan rule lama jika rollback
         * memang dilakukan.
         */
        $this->createUniqueIndexIfMissing(
            self::OLD_KELURAHAN_UNIQUE,
            [
                'period_id',
                'kelurahan_id',
            ]
        );
    }

    private function removeDuplicateSurveyorAssignments(): void
    {
        $duplicates =
            DB::table(
                self::TABLE
            )
                ->select([
                    'period_id',
                    'surveyor_id',
                ])
                ->groupBy([
                    'period_id',
                    'surveyor_id',
                ])
                ->havingRaw(
                    'COUNT(*) > 1'
                )
                ->get();

        foreach (
            $duplicates
            as $duplicate
        ) {
            /*
             * Ambil seluruh assignment Surveyor
             * pada periode yang sama.
             */
            $ids =
                DB::table(
                    self::TABLE
                )
                    ->where(
                        'period_id',
                        $duplicate->period_id
                    )
                    ->where(
                        'surveyor_id',
                        $duplicate->surveyor_id
                    )
                    ->orderBy(
                        'id'
                    )
                    ->pluck(
                        'id'
                    );

            /*
             * Assignment pertama dipertahankan.
             * Sisanya merupakan data yang tidak
             * sesuai dengan business rule baru.
             */
            $idsToDelete =
                $ids
                    ->slice(1)
                    ->values();

            if (
                $idsToDelete
                    ->isEmpty()
            ) {
                continue;
            }

            DB::table(
                self::TABLE
            )
                ->whereIn(
                    'id',
                    $idsToDelete->all()
                )
                ->delete();
        }
    }

    private function indexExists(
        string $indexName
    ): bool {
        $driver =
            DB::connection()
                ->getDriverName();

        /*
         |--------------------------------------------------------------------------
         | SQLite
         |--------------------------------------------------------------------------
         |
         | Automated test project menggunakan:
         |
         | sqlite :memory:
         |
         | SQLite tidak mempunyai:
         |
         | information_schema.statistics
         |
         | sehingga harus menggunakan PRAGMA.
         |
         */
        if (
            $driver === 'sqlite'
        ) {
            $indexes =
                DB::select(
                    "PRAGMA index_list('"
                    . self::TABLE
                    . "')"
                );

            foreach (
                $indexes
                as $index
            ) {
                if (
                    isset(
                        $index->name
                    )
                    &&
                    (string) $index->name
                    ===
                    $indexName
                ) {
                    return true;
                }
            }

            return false;
        }

        /*
         |--------------------------------------------------------------------------
         | MySQL
         |--------------------------------------------------------------------------
         |
         | Database aplikasi sebenarnya menggunakan
         | MySQL sehingga information_schema aman
         | digunakan hanya pada driver MySQL.
         |
         */
        if (
            $driver === 'mysql'
        ) {
            $rows =
                DB::select(
                    <<<'SQL'
                        SELECT 1
                        FROM information_schema.statistics
                        WHERE table_schema = DATABASE()
                          AND table_name = ?
                          AND index_name = ?
                        LIMIT 1
                    SQL,
                    [
                        self::TABLE,
                        $indexName,
                    ]
                );

            return $rows !== [];
        }

        throw new RuntimeException(
            sprintf(
                'Database driver "%s" belum didukung oleh migration Surveyor Assignment.',
                $driver
            )
        );
    }

    private function dropIndexIfExists(
        string $indexName
    ): void {
        if (
            ! $this->indexExists(
                $indexName
            )
        ) {
            return;
        }

        $driver =
            DB::connection()
                ->getDriverName();

        /*
         * SQLite.
         */
        if (
            $driver === 'sqlite'
        ) {
            DB::statement(
                sprintf(
                    'DROP INDEX "%s"',
                    $indexName
                )
            );

            return;
        }

        /*
         * MySQL.
         */
        if (
            $driver === 'mysql'
        ) {
            DB::statement(
                sprintf(
                    'ALTER TABLE `%s` DROP INDEX `%s`',
                    self::TABLE,
                    $indexName
                )
            );

            return;
        }

        throw new RuntimeException(
            sprintf(
                'Database driver "%s" belum didukung oleh migration Surveyor Assignment.',
                $driver
            )
        );
    }

    private function createIndexIfMissing(
        string $indexName,
        array $columns
    ): void {
        if (
            $this->indexExists(
                $indexName
            )
        ) {
            return;
        }

        $this->createIndex(
            $indexName,
            $columns,
            false
        );
    }

    private function createUniqueIndexIfMissing(
        string $indexName,
        array $columns
    ): void {
        if (
            $this->indexExists(
                $indexName
            )
        ) {
            return;
        }

        $this->createIndex(
            $indexName,
            $columns,
            true
        );
    }

    private function createIndex(
        string $indexName,
        array $columns,
        bool $unique
    ): void {
        $driver =
            DB::connection()
                ->getDriverName();

        /*
         |--------------------------------------------------------------------------
         | SQLite
         |--------------------------------------------------------------------------
         */
        if (
            $driver === 'sqlite'
        ) {
            $quotedColumns =
                implode(
                    ', ',
                    array_map(
                        static fn (
                            string $column
                        ): string =>
                            sprintf(
                                '"%s"',
                                $column
                            ),
                        $columns
                    )
                );

            DB::statement(
                sprintf(
                    'CREATE %sINDEX "%s" ON "%s" (%s)',
                    $unique
                        ? 'UNIQUE '
                        : '',
                    $indexName,
                    self::TABLE,
                    $quotedColumns
                )
            );

            return;
        }

        /*
         |--------------------------------------------------------------------------
         | MySQL
         |--------------------------------------------------------------------------
         */
        if (
            $driver === 'mysql'
        ) {
            $quotedColumns =
                implode(
                    ', ',
                    array_map(
                        static fn (
                            string $column
                        ): string =>
                            sprintf(
                                '`%s`',
                                $column
                            ),
                        $columns
                    )
                );

            DB::statement(
                sprintf(
                    'ALTER TABLE `%s` ADD %sINDEX `%s` (%s)',
                    self::TABLE,
                    $unique
                        ? 'UNIQUE '
                        : '',
                    $indexName,
                    $quotedColumns
                )
            );

            return;
        }

        throw new RuntimeException(
            sprintf(
                'Database driver "%s" belum didukung oleh migration Surveyor Assignment.',
                $driver
            )
        );
    }
};