<?php

declare(strict_types=1);

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel pengguna, reset password, dan session.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();

            $table->string('name', 150);
            $table->string('username', 60)->unique();
            $table->string('email', 150)->nullable()->unique();
            $table->string('phone', 20)->nullable();

            $table
                ->string('role', 30)
                ->default(UserRole::SURVEYOR->value)
                ->index();

            $table->boolean('is_active')->default(true)->index();

            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();

            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create(
            'password_reset_tokens',
            function (Blueprint $table): void {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            }
        );

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();

            $table
                ->foreignId('user_id')
                ->nullable()
                ->index()
                ->constrained()
                ->nullOnDelete();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Menghapus seluruh tabel autentikasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};