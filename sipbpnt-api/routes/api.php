<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\BnbaImportController;
use App\Http\Controllers\Api\V1\Admin\BpntPeriodController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\Management\BnbaParticipantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Version 1
|--------------------------------------------------------------------------
|
| Seluruh endpoint aplikasi SIPBPNT diletakkan di bawah prefix /api/v1.
|
*/

Route::prefix('v1')->group(function (): void {
    /*
    |--------------------------------------------------------------------------
    | Public
    |--------------------------------------------------------------------------
    */

    Route::get('/health', function () {
        return response()->json([
            'application' => 'SIPBPNT',
            'status' => 'healthy',
            'timestamp' => now()
                ->timezone('Asia/Jakarta')
                ->toIso8601String(),
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | Login menggunakan Laravel Sanctum session-cookie.
    |
    */

    Route::post(
        '/auth/login',
        [
            AuthController::class,
            'login',
        ]
    )->middleware('throttle:10,1');

    /*
    |--------------------------------------------------------------------------
    | Authenticated Users
    |--------------------------------------------------------------------------
    |
    | Semua user yang sudah login dan masih aktif dapat mengakses endpoint
    | berikut.
    |
    */

    Route::middleware([
        'auth:sanctum',
        'active.user',
    ])->group(function (): void {
        Route::get(
            '/auth/me',
            [
                AuthController::class,
                'me',
            ]
        );

        Route::post(
            '/auth/logout',
            [
                AuthController::class,
                'logout',
            ]
        );

        Route::get(
            '/dashboard',
            [
                DashboardController::class,
                'index',
            ]
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Admin + Manager
    |--------------------------------------------------------------------------
    |
    | Admin dan Manager boleh membaca daftar periode serta data BNBA yang
    | sudah dikonfirmasi.
    |
    | Manager TIDAK boleh membuat periode dan TIDAK boleh melakukan import.
    |
    */

    Route::middleware([
        'auth:sanctum',
        'active.user',
        'role:admin_dinsos,manager',
    ])->group(function (): void {
        /*
        |--------------------------------------------------------------------------
        | Periode BPNT
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/bpnt-periods',
            [
                BpntPeriodController::class,
                'index',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | BNBA Terkonfirmasi
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/bnba/participants/options',
            [
                BnbaParticipantController::class,
                'options',
            ]
        );

        Route::get(
            '/bnba/participants',
            [
                BnbaParticipantController::class,
                'index',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Access Check
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/management/check-access',
            function () {
                return response()->json([
                    'message' => 'Akses manajemen diberikan.',
                ]);
            }
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Dinas Sosial
    |--------------------------------------------------------------------------
    |
    | Endpoint yang dapat mengubah data BNBA hanya tersedia untuk
    | admin_dinsos.
    |
    */

    Route::middleware([
        'auth:sanctum',
        'active.user',
        'role:admin_dinsos',
    ])->group(function (): void {
        /*
        |--------------------------------------------------------------------------
        | Periode BPNT
        |--------------------------------------------------------------------------
        |
        | GET periode berada pada group Admin + Manager.
        | POST periode hanya Admin.
        |
        */

        Route::post(
            '/bpnt-periods',
            [
                BpntPeriodController::class,
                'store',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Import BNBA
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/bnba/imports',
            [
                BnbaImportController::class,
                'index',
            ]
        );

        Route::post(
            '/bnba/imports',
            [
                BnbaImportController::class,
                'store',
            ]
        );

        Route::get(
            '/bnba/imports/{import}/preview',
            [
                BnbaImportController::class,
                'preview',
            ]
        )->whereNumber('import');

        Route::post(
            '/bnba/imports/{import}/confirm',
            [
                BnbaImportController::class,
                'confirm',
            ]
        )->whereNumber('import');
    });

    /*
    |--------------------------------------------------------------------------
    | Kepala Dinas
    |--------------------------------------------------------------------------
    |
    | Untuk saat ini baru berupa access check.
    | Monitoring Kepala Dinas akan ditambahkan sebagai modul terpisah.
    |
    */

    Route::middleware([
        'auth:sanctum',
        'active.user',
        'role:kepala_dinas',
    ])->group(function (): void {
        Route::get(
            '/head-office/check-access',
            function () {
                return response()->json([
                    'message' => 'Akses Kepala Dinas diberikan.',
                ]);
            }
        );
    });
});