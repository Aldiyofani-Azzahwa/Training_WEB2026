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
            'kpm_verifications',
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

                $table->foreignId('participant_kelurahan_id')
                    ->constrained('kelurahans')
                    ->restrictOnDelete();

                $table->foreignId('surveyor_kelurahan_id')
                    ->constrained('kelurahans')
                    ->restrictOnDelete();

                $table->string('status', 30);
                $table->text('reason')->nullable();

                /*
                 * 1    = verifikasi aktif/final
                 * NULL = telah dibatalkan Manager
                 */
                $table->unsignedTinyInteger('active_slot')
                    ->nullable()
                    ->default(1);

                $table->timestamp('verified_at');

                $table->foreignId('cancelled_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();

                $table->unique(
                    [
                        'period_id',
                        'bpnt_participant_id',
                        'active_slot',
                    ],
                    'kpm_verification_active_unique'
                );

                $table->index(
                    [
                        'period_id',
                        'status',
                        'active_slot',
                    ],
                    'kpm_verification_period_status_index'
                );

                $table->index(
                    [
                        'surveyor_id',
                        'verified_at',
                    ],
                    'kpm_verification_surveyor_time_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('kpm_verifications');
    }
};