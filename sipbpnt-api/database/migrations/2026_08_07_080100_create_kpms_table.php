<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpms', function (Blueprint $table): void {
            $table->id();

            $table->char('nik_hash', 64)->unique();
            $table->text('nik_ciphertext');

            $table->char('nkk_hash', 64)->index();
            $table->text('nkk_ciphertext');

            $table->string('full_name', 150)->index();
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('mother_name', 150)->nullable();

            $table->text('address');

            $table->string('rt', 3)->nullable();
            $table->string('rw', 3)->nullable();

            $table->string('kelurahan', 100)->index();
            $table->string('kecamatan', 100)->index();

            $table->text('account_ciphertext')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpms');
    }
};