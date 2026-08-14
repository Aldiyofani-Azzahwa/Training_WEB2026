<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(
        function (Middleware $middleware): void {
            /*
             * Mengaktifkan autentikasi Sanctum berbasis
             * session cookie untuk Vue SPA.
             */
            $middleware->statefulApi();

            $middleware->alias([
                'active.user' => EnsureActiveUser::class,
                'role' => RoleMiddleware::class,
            ]);
        }
    )
    ->withExceptions(
        function (Exceptions $exceptions): void {
            /*
             * Semua request API selalu menerima response JSON,
             * termasuk error validasi dan autentikasi.
             */
            $exceptions->shouldRenderJsonWhen(
                static function (
                    Request $request,
                    \Throwable $exception
                ): bool {
                    return $request->is('api/*')
                        || $request->expectsJson();
                }
            );
        }
    )
    ->create();