<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\BnbaImportController;
use App\Http\Controllers\Api\V1\Admin\BpntPeriodController;
use App\Http\Controllers\Api\V1\Admin\SurveyorController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Management\BnbaParticipantController;
use App\Http\Controllers\Api\V1\Management\SurveyorOptionController;
use App\Http\Controllers\Api\V1\Management\WilayahController;
use Illuminate\Support\Facades\Route;

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
                    'application'
                        => 'SIPBPNT',

                    'status'
                        => 'healthy',

                    'timestamp'
                        => now()
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
        */

        Route::middleware([
            'auth:sanctum',
            'active.user',
            'role:admin_dinsos,manager',
        ])->group(function (): void {
            Route::get(
                '/bpnt-periods',
                [
                    BpntPeriodController::class,
                    'index',
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Wilayah
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/wilayah',
                [
                    WilayahController::class,
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
            | Surveyor Aktif
            |--------------------------------------------------------------------------
            |
            | Dipakai Manager pada Assignment.
            |
            */

            Route::get(
                '/surveyors/options',
                [
                    SurveyorOptionController::class,
                    'index',
                ]
            );

            Route::get(
                '/management/check-access',
                function () {
                    return response()->json([
                        'message'
                            => 'Akses manajemen diberikan.',
                    ]);
                }
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Admin Dinas Sosial
        |--------------------------------------------------------------------------
        */

        Route::middleware([
            'auth:sanctum',
            'active.user',
            'role:admin_dinsos',
        ])->group(function (): void {
            /*
            |--------------------------------------------------------------------------
            | Master Akun Surveyor
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/admin/surveyors',
                [
                    SurveyorController::class,
                    'index',
                ]
            );

            Route::post(
                '/admin/surveyors',
                [
                    SurveyorController::class,
                    'store',
                ]
            );

            Route::patch(
                '/admin/surveyors/{surveyor}',
                [
                    SurveyorController::class,
                    'update',
                ]
            )->whereNumber(
                'surveyor'
            );

            Route::patch(
                '/admin/surveyors/{surveyor}/status',
                [
                    SurveyorController::class,
                    'updateStatus',
                ]
            )->whereNumber(
                'surveyor'
            );

            /*
             * PENTING:
             *
             * Tidak ada DELETE route Surveyor.
             *
             * Surveyor lama harus dinonaktifkan,
             * bukan dihapus.
             */

            /*
            |--------------------------------------------------------------------------
            | Periode BPNT
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/bpnt-periods',
                [
                    BpntPeriodController::class,
                    'store',
                ]
            );

            Route::patch(
                '/bpnt-periods/{period}',
                [
                    BpntPeriodController::class,
                    'update',
                ]
            )->whereNumber(
                'period'
            );

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
            )->whereNumber(
                'import'
            );

            Route::post(
                '/bnba/imports/{import}/confirm',
                [
                    BnbaImportController::class,
                    'confirm',
                ]
            )->whereNumber(
                'import'
            );

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
                        'message'
                            => 'Akses Kepala Dinas diberikan.',
                    ]);
                }
            );
        });
    });