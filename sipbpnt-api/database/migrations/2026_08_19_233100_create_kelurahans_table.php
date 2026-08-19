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
            'kelurahans',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('kecamatan_id')
                    ->constrained('kecamatans')
                    ->restrictOnDelete();

                /*
                 * Kode kelurahan pada sumber
                 * wilayah resmi bersifat unik
                 * di dalam kecamatan.
                 */
                $table->string(
                    'code',
                    4
                );

                $table
                    ->string('name', 100)
                    ->index();

                $table->timestamps();

                $table->unique(
                    [
                        'kecamatan_id',
                        'code',
                    ],
                    'kelurahan_kecamatan_code_unique'
                );

                $table->unique(
                    [
                        'kecamatan_id',
                        'name',
                    ],
                    'kelurahan_kecamatan_name_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'kelurahans'
        );
    }
};