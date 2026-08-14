<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bnba_import_rows', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bnba_import_id')
                ->constrained('bnba_imports')
                ->cascadeOnDelete();

            $table->unsignedInteger('row_number');
            $table->string('status', 20)->index();

            $table->string('membership_year', 4)->nullable();

            $table->char('nik_hash', 64)->nullable()->index();
            $table->text('nik_ciphertext')->nullable();

            $table->char('nkk_hash', 64)->nullable()->index();
            $table->text('nkk_ciphertext')->nullable();

            $table->string('full_name', 150)->nullable()->index();
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('mother_name', 150)->nullable();

            $table->text('address')->nullable();

            $table->string('rt', 3)->nullable();
            $table->string('rw', 3)->nullable();

            $table->string('kelurahan', 100)->nullable()->index();
            $table->string('kecamatan', 100)->nullable()->index();

            $table->text('account_ciphertext')->nullable();

            $table->string('e_warung_name', 200)
                ->nullable()
                ->index();

            $table->string('source_status', 100)->nullable();
            $table->string('source_description', 255)->nullable();

            $table->json('monthly_statuses')->nullable();

            $table->string('sk_status', 100)->nullable();
            $table->string('sk_description', 255)->nullable();

            $table->string('apbn_march_status', 255)->nullable();

            $table->unsignedTinyInteger('welfare_rank')->nullable();

            $table->unsignedBigInteger('nominal')->nullable();

            $table->json('errors')->nullable();
            $table->json('warnings')->nullable();

            $table->timestamps();

            $table->unique([
                'bnba_import_id',
                'row_number',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bnba_import_rows');
    }
};