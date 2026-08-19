<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME =
        'bnba_imports_period_unique';

    public function up(): void
    {
        /*
         * Jangan membuat UNIQUE constraint
         * jika database sudah mempunyai
         * lebih dari satu BNBA pada periode
         * yang sama.
         *
         * Data tidak boleh dihapus otomatis.
         */
        $hasDuplicatePeriods =
            DB::table('bnba_imports')
                ->select(
                    'bpnt_period_id'
                )
                ->groupBy(
                    'bpnt_period_id'
                )
                ->havingRaw(
                    'COUNT(*) > 1'
                )
                ->exists();

        if ($hasDuplicatePeriods) {
            throw new \RuntimeException(
                'Migration dibatalkan: terdapat lebih dari satu BNBA pada periode yang sama. Periksa data bnba_imports sebelum melanjutkan.'
            );
        }

        /*
         * Database menjadi pagar terakhir:
         *
         * satu period_id
         * hanya boleh muncul satu kali
         * pada bnba_imports.
         */
        Schema::table(
            'bnba_imports',
            function (
                Blueprint $table
            ): void {
                $table->unique(
                    'bpnt_period_id',
                    self::INDEX_NAME
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'bnba_imports',
            function (
                Blueprint $table
            ): void {
                $table->dropUnique(
                    self::INDEX_NAME
                );
            }
        );
    }
};