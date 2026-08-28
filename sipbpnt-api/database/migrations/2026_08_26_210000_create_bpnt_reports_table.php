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
            'bpnt_reports',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('period_id')
                    ->constrained('bpnt_periods')
                    ->restrictOnDelete();

                $table->string('status', 20);

                $table->json('summary');
                $table->json('snapshot');

                $table->foreignId('finalized_by')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->timestamp('finalized_at');
                $table->timestamps();

                $table->unique(
                    'period_id',
                    'bpnt_report_period_unique'
                );

                $table->index(
                    [
                        'status',
                        'finalized_at',
                    ],
                    'bpnt_report_status_time_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('bpnt_reports');
    }
};