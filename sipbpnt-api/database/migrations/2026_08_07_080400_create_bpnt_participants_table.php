<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bpnt_participants', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bpnt_period_id')
                ->constrained('bpnt_periods')
                ->restrictOnDelete();

            $table->foreignId('kpm_id')
                ->constrained('kpms')
                ->restrictOnDelete();

            $table->foreignId('bnba_import_id')
                ->constrained('bnba_imports')
                ->restrictOnDelete();

            $table->unsignedInteger('import_row_number');

            $table->string('membership_year', 4)->nullable();

            $table->string(
                'e_warung_name_source',
                200
            )->nullable()->index();

            $table->string('source_status', 100)->nullable();
            $table->string('source_description', 255)->nullable();

            $table->json('monthly_statuses')->nullable();

            $table->string('sk_status', 100)->nullable();
            $table->string('sk_description', 255)->nullable();

            $table->string('apbn_march_status', 255)->nullable();

            $table->unsignedTinyInteger('welfare_rank')->nullable();

            $table->unsignedBigInteger(
                'entitlement_amount'
            )->default(0);

            $table->timestamps();

            $table->unique(
                [
                    'bpnt_period_id',
                    'kpm_id',
                ],
                'bpnt_participant_period_kpm_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bpnt_participants');
    }
};