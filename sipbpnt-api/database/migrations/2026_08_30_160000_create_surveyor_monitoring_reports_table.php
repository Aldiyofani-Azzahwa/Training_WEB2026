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
            'surveyor_monitoring_reports',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('period_id')
                    ->constrained('bpnt_periods')
                    ->cascadeOnDelete();

                $table->foreignId('assignment_id')
                    ->constrained('surveyor_assignments')
                    ->cascadeOnDelete();

                $table->foreignId('surveyor_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->foreignId('kelurahan_id')
                    ->constrained('kelurahans')
                    ->restrictOnDelete();

                $table->json('commodities');

                $table->string(
                    'social_officer_name',
                    150
                )->nullable();

                $table->string(
                    'distribution_assistant_name',
                    150
                )->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'period_id',
                        'surveyor_id',
                    ],
                    'surveyor_monitoring_period_surveyor_unique'
                );

                $table->index(
                    [
                        'period_id',
                        'kelurahan_id',
                    ],
                    'surveyor_monitoring_period_kelurahan_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'surveyor_monitoring_reports'
        );
    }
};