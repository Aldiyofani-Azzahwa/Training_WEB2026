<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'transactions',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('period_id')
                    ->constrained('bpnt_periods')
                    ->restrictOnDelete();

                $table->foreignId('bpnt_participant_id')
                    ->constrained('bpnt_participants')
                    ->restrictOnDelete();

                $table->foreignId('surveyor_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->foreignId('e_warung_id')
                    ->constrained('e_warungs')
                    ->restrictOnDelete();

                $table->foreignId('participant_kelurahan_id')
                    ->constrained('kelurahans')
                    ->restrictOnDelete();

                $table->foreignId('surveyor_kelurahan_id')
                    ->constrained('kelurahans')
                    ->restrictOnDelete();

                $table->timestamp('transacted_at');
                $table->timestamps();

                $table->unique(
                    [
                        'period_id',
                        'bpnt_participant_id',
                    ],
                    'transaction_period_participant_unique'
                );

                $table->index(
                    [
                        'surveyor_id',
                        'transacted_at',
                    ],
                    'transaction_surveyor_time_index'
                );

                $table->index(
                    [
                        'period_id',
                        'e_warung_id',
                    ],
                    'transaction_period_e_warung_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};