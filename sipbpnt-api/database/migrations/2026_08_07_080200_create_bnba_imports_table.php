<?php

declare(strict_types=1);

use App\Enums\BnbaImportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bnba_imports', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bpnt_period_id')
                ->constrained('bpnt_periods')
                ->restrictOnDelete();

            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('confirmed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status', 30)
                ->default(BnbaImportStatus::PREVIEW_READY->value)
                ->index();

            $table->string('original_name', 255);
            $table->string('stored_path', 500);
            $table->char('file_sha256', 64)->index();

            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('warning_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);

            $table->timestamp('confirmed_at')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index([
                'bpnt_period_id',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bnba_imports');
    }
};