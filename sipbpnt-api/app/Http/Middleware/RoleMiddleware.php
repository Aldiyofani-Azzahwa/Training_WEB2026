<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Membatasi endpoint berdasarkan role pengguna.
     *
     * Contoh:
     * middleware('role:admin_dinsos,manager')
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Pengguna belum terautentikasi.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $allowedRoles = array_map(
            static function (string $role): string {
                return UserRole::tryFrom($role)?->value ?? $role;
            },
            $roles
        );

        if (! in_array(
            $user->role->value,
            $allowedRoles,
            true
        )) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke fitur ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}