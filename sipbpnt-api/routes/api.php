<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\BnbaImportController;
use App\Http\Controllers\Api\V1\Admin\BpntPeriodController;
use App\Http\Controllers\Api\V1\Admin\EWarungController;
use App\Http\Controllers\Api\V1\Admin\SurveyorController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Management\BnbaParticipantController;
use App\Http\Controllers\Api\V1\Management\SurveyorOptionController;
use App\Http\Controllers\Api\V1\Management\WilayahController;
use App\Http\Controllers\Api\V1\Manager\SurveyorAssignmentController;
use App\Http\Controllers\Api\V1\Surveyor\SurveyorWorkspaceController;
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
        | Authenticated
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
        | Active BPNT Period
        |--------------------------------------------------------------------------
        |
        | Seluruh role internal boleh membaca
        | periode yang sedang digunakan.
        |
        | Hanya Admin Dinsos yang boleh
        | mengaktifkan / menonaktifkan.
        |
        */

        Route::middleware([
            'auth:sanctum',
            'active.user',
            'role:admin_dinsos,manager,surveyor,kepala_dinas',
        ])->group(function (): void {
            Route::get(
                '/bpnt-periods/active',
                [
                    BpntPeriodController::class,
                    'active',
                ]
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Admin + Manager Shared Data
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

            Route::get(
                '/wilayah',
                [
                    WilayahController::class,
                    'index',
                ]
            );

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
            | Master E-Warung
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/admin/e-warungs',
                [
                    EWarungController::class,
                    'index',
                ]
            );

            Route::post(
                '/admin/e-warungs',
                [
                    EWarungController::class,
                    'store',
                ]
            );

            Route::patch(
                '/admin/e-warungs/{eWarung}',
                [
                    EWarungController::class,
                    'update',
                ]
            )->whereNumber(
                'eWarung'
            );

            Route::patch(
                '/admin/e-warungs/{eWarung}/status',
                [
                    EWarungController::class,
                    'updateStatus',
                ]
            )->whereNumber(
                'eWarung'
            );

            Route::delete(
                '/admin/e-warungs/{eWarung}',
                [
                    EWarungController::class,
                    'destroy',
                ]
            )->whereNumber(
                'eWarung'
            );

            /*
            |--------------------------------------------------------------------------
            | Surveyor Account
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
            |--------------------------------------------------------------------------
            | Periode
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

            Route::put(
                '/bpnt-periods/{period}/activate',
                [
                    BpntPeriodController::class,
                    'activate',
                ]
            )->whereNumber(
                'period'
            );

            Route::put(
                '/bpnt-periods/{period}/deactivate',
                [
                    BpntPeriodController::class,
                    'deactivate',
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
            | BNBA
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
        | Manager
        |--------------------------------------------------------------------------
        */

        Route::middleware([
            'auth:sanctum',
            'active.user',
            'role:manager',
        ])->group(function (): void {
            Route::get(
                '/manager/surveyor-assignments',
                [
                    SurveyorAssignmentController::class,
                    'index',
                ]
            );

            Route::put(
                '/manager/surveyor-assignments',
                [
                    SurveyorAssignmentController::class,
                    'store',
                ]
            );

            Route::delete(
                '/manager/surveyor-assignments/{assignment}',
                [
                    SurveyorAssignmentController::class,
                    'destroy',
                ]
            )->whereNumber(
                'assignment'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Surveyor
        |--------------------------------------------------------------------------
        |
        | Surveyor tidak mengirim:
        |
        | - period_id
        | - kecamatan_id
        | - kelurahan_id
        |
        | Semua konteks diambil backend
        | dari periode aktif + assignment.
        |
        */

        Route::middleware([
            'auth:sanctum',
            'active.user',
            'role:surveyor',
        ])->group(function (): void {
            /*
             * Context:
             *
             * - Surveyor
             * - periode aktif
             * - wilayah assignment
             * - jumlah KPM wilayah
             */
            Route::get(
                '/surveyor/context',
                [
                    SurveyorWorkspaceController::class,
                    'context',
                ]
            );

            /*
             * Browse KPM.
             *
             * HANYA participant kelurahan
             * assignment Surveyor.
             */
            Route::get(
                '/surveyor/participants',
                [
                    SurveyorWorkspaceController::class,
                    'participants',
                ]
            );

            /*
             * Exact NIK lookup.
             *
             * Boleh mencari seluruh participant
             * pada periode aktif.
             *
             * KPM luar wilayah tidak ditolak.
             */
            Route::post(
                '/surveyor/lookup-nik',
                [
                    SurveyorWorkspaceController::class,
                    'lookupNik',
                ]
            )->middleware(
                'throttle:60,1'
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