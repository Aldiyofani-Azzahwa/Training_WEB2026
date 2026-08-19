<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $activePeriodIds =
            DB::table('bpnt_periods')
                ->where('is_active', true)
                ->orderBy('id')
                ->pluck('id');

        if ($activePeriodIds->count() > 1) {
            throw new \RuntimeException(
                'Migration dibatalkan karena terdapat '
                .'lebih dari satu periode BPNT aktif. '
                .'Tentukan satu periode aktif yang benar '
                .'berdasarkan periode resmi pemerintah '
                .'sebelum menjalankan migration kembali.'
            );
        }

        Schema::table(
            'bpnt_periods',
            function (Blueprint $table): void {
                $table
                    ->unsignedTinyInteger(
                        'active_slot'
                    )
                    ->nullable()
                    ->after('is_active');
            }
        );

        if ($activePeriodIds->count() === 1) {
            DB::table('bpnt_periods')
                ->where(
                    'id',
                    (int) $activePeriodIds->first()
                )
                ->update([
                    'active_slot' => 1,
                ]);
        }

        Schema::table(
            'bpnt_periods',
            function (Blueprint $table): void {
                $table->unique(
                    'active_slot',
                    'bpnt_period_single_active_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'bpnt_periods',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'bpnt_period_single_active_unique'
                );

                $table->dropColumn(
                    'active_slot'
                );
            }
        );
    }
};