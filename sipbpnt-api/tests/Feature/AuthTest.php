<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private const SPA_ORIGIN = 'http://localhost:5173';

    protected function setUp(): void
    {
        parent::setUp();

        /*
        |--------------------------------------------------------------------------
        | Sanctum Stateful Domain
        |--------------------------------------------------------------------------
        |
        | Feature test harus mensimulasikan request yang benar-benar datang
        | dari frontend Vue. Tanpa Origin / Referer, Sanctum menganggap request
        | sebagai request API stateless sehingga session tidak dimulai.
        |
        */
        config()->set('sanctum.stateful', [
            'localhost:5173',
        ]);
    }

    public function test_active_user_can_login(): void
    {
        User::factory()->create([
            'name' => 'Surveyor Test',
            'username' => 'surveyor.test',
            'email' => 'surveyor.test@example.test',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::SURVEYOR,
            'is_active' => true,
        ]);

        $response = $this
            ->withHeaders($this->spaHeaders())
            ->postJson(
                '/api/v1/auth/login',
                [
                    'username' => 'surveyor.test',
                    'password' => 'Password123!',
                    'remember' => false,
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.username',
                'surveyor.test'
            );

        $this->assertAuthenticated('web');
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'name' => 'Inactive User',
            'username' => 'inactive.test',
            'email' => 'inactive.test@example.test',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::SURVEYOR,
            'is_active' => false,
        ]);

        $response = $this
            ->withHeaders($this->spaHeaders())
            ->postJson(
                '/api/v1/auth/login',
                [
                    'username' => 'inactive.test',
                    'password' => 'Password123!',
                    'remember' => false,
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'username',
            ]);

        $this->assertGuest('web');
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create([
            'name' => 'Surveyor Logout Test',
            'username' => 'surveyor.logout',
            'email' => 'surveyor.logout@example.test',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::SURVEYOR,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user, 'web')
            ->withHeaders($this->spaHeaders())
            ->postJson(
                '/api/v1/auth/logout'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Logout berhasil.'
            );

        $this->assertGuest('web');
    }

    /**
     * Header yang meniru request SPA Vue.
     *
     * @return array<string, string>
     */
    private function spaHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Origin' => self::SPA_ORIGIN,
            'Referer' => self::SPA_ORIGIN.'/',
        ];
    }
}