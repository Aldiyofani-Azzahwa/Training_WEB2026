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
            'surveyor_assignments',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('period_id')
                    ->constrained('bpnt_periods')
                    ->restrictOnDelete();

                $table->foreignId('surveyor_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->foreignId('kelurahan_id')
                    ->constrained('kelurahans')
                    ->restrictOnDelete();

                $table->foreignId('assigned_by')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->timestamp('assigned_at');

                $table->timestamps();

                $table->unique(
                    [
                        'period_id',
                        'kelurahan_id',
                    ],
                    'surveyor_assignment_period_kelurahan_unique'
                );

                $table->index(
                    [
                        'period_id',
                        'surveyor_id',
                    ],
                    'surveyor_assignment_period_surveyor_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'surveyor_assignments'
        );
    }
};