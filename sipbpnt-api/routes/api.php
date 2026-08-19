<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\BnbaImportController;
use App\Http\Controllers\Api\V1\Admin\BpntPeriodController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Management\BnbaParticipantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Version 1
|--------------------------------------------------------------------------
|
| Seluruh endpoint SIPBPNT berada
| di bawah prefix /api/v1.
|
*/

Route::prefix('v1')
    ->group(function (): void {
        /*
        |--------------------------------------------------------------------------
        | Public
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/health',
            function () {
                return response()->json([
                    'application' => 'SIPBPNT',

                    'status' => 'healthy',

                    'timestamp' => now()
                        ->timezone(
                            'Asia/Jakarta'
                        )
                        ->toIso8601String(),
                ]);
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/auth/login',
            [
                AuthController::class,
                'login',
            ]
        )->middleware(
            'throttle:10,1'
        );

        /*
        |--------------------------------------------------------------------------
        | Authenticated User
        |--------------------------------------------------------------------------
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
        });

        /*
        |--------------------------------------------------------------------------
        | Admin + Manager
        |--------------------------------------------------------------------------
        |
        | Admin dan Manager boleh membaca:
        |
        | - daftar periode BPNT
        | - data BNBA terkonfirmasi
        | - pilihan filter BNBA
        |
        | Manager tidak boleh melakukan perubahan data.
        |
        */

        Route::middleware([
            'auth:sanctum',
            'active.user',
            'role:admin_dinsos,manager',
        ])->group(function (): void {
            /*
            |--------------------------------------------------------------------------
            | Periode BPNT - Read
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
            | BNBA Confirmed
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
            | Management Access Check
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/management/check-access',
                function () {
                    return response()->json([
                        'message' =>
                            'Akses manajemen diberikan.',
                    ]);
                }
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Admin Dinas Sosial
        |--------------------------------------------------------------------------
        |
        | Hanya Admin Dinas Sosial yang boleh:
        |
        | - membuat periode
        | - mengedit periode
        | - menghapus periode kosong
        | - upload BNBA
        | - melihat preview import
        | - mengonfirmasi import
        | - menghapus BNBA suatu periode
        |
        */

        Route::middleware([
            'auth:sanctum',
            'active.user',
            'role:admin_dinsos',
        ])->group(function (): void {
            /*
            |--------------------------------------------------------------------------
            | Periode BPNT - Create
            |--------------------------------------------------------------------------
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
            | Periode BPNT - Update
            |--------------------------------------------------------------------------
            |
            | Nama periode dapat diedit.
            |
            | Tahun hanya dapat diubah
            | apabila periode belum memiliki BNBA.
            |
            */

            Route::patch(
                '/bpnt-periods/{period}',
                [
                    BpntPeriodController::class,
                    'update',
                ]
            )->whereNumber(
                'period'
            );

            /*
            |--------------------------------------------------------------------------
            | Periode BPNT - Delete
            |--------------------------------------------------------------------------
            |
            | Periode hanya dapat dihapus apabila:
            |
            | imports = 0
            | participants = 0
            |
            | Business rule tetap diperiksa
            | kembali pada BpntPeriodService.
            |
            */

            Route::delete(
                '/bpnt-periods/{period}',
                [
                    BpntPeriodController::class,
                    'destroy',
                ]
            )->whereNumber(
                'period'
            );

            /*
            |--------------------------------------------------------------------------
            | Import History
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/bnba/imports',
                [
                    BnbaImportController::class,
                    'index',
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Upload BNBA
            |--------------------------------------------------------------------------
            |
            | Satu periode hanya memiliki
            | satu BNBA.
            |
            | Apabila BNBA sudah ada,
            | Admin harus menghapus BNBA tersebut
            | terlebih dahulu sebelum upload ulang.
            |
            */

            Route::post(
                '/bnba/imports',
                [
                    BnbaImportController::class,
                    'store',
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Preview BNBA
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/bnba/imports/{import}/preview',
                [
                    BnbaImportController::class,
                    'preview',
                ]
            )->whereNumber(
                'import'
            );

            /*
            |--------------------------------------------------------------------------
            | Confirm BNBA
            |--------------------------------------------------------------------------
            |
            | Baris valid dan warning
            | menjadi participant periode.
            |
            */

            Route::post(
                '/bnba/imports/{import}/confirm',
                [
                    BnbaImportController::class,
                    'confirm',
                ]
            )->whereNumber(
                'import'
            );

            /*
            |--------------------------------------------------------------------------
            | Hapus BNBA Periode
            |--------------------------------------------------------------------------
            |
            | Digunakan apabila Admin salah upload
            | atau ingin mengganti file BNBA.
            |
            | Flow:
            |
            | Hapus BNBA
            |     ↓
            | periode kembali kosong
            |     ↓
            | upload file BNBA baru
            |
            | BnbaImportService bertanggung jawab
            | menghapus participant, staging/import,
            | file sumber, dan mencatat audit log.
            |
            */

            Route::delete(
                '/bpnt-periods/{period}/bnba',
                [
                    BnbaImportController::class,
                    'destroyForPeriod',
                ]
            )->whereNumber(
                'period'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Kepala Dinas
        |--------------------------------------------------------------------------
        |
        | Modul monitoring Kepala Dinas
        | belum dibangun pada tahap ini.
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
                        'message' =>
                            'Akses Kepala Dinas diberikan.',
                    ]);
                }
            );
        });
    });