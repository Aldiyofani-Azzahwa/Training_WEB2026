<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\SurveyorKpmActivityRepositoryInterface;
use App\Http\Controllers\Api\V1\Manager\ManagerKpmVerificationController;
use App\Http\Controllers\Api\V1\Surveyor\SurveyorKpmActivityController;
use App\Repositories\EloquentSurveyorKpmActivityRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class KpmActivityServiceProvider
    extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SurveyorKpmActivityRepositoryInterface::class,
            EloquentSurveyorKpmActivityRepository::class
        );
    }

    public function boot(): void
    {
        Route::prefix('api/v1')
            ->middleware([
                'api',
                'auth:sanctum',
                'active.user',
            ])
            ->group(function (): void {
                Route::middleware('role:surveyor')
                    ->prefix('surveyor')
                    ->group(function (): void {
                        Route::get(
                            '/e-warungs',
                            [
                                SurveyorKpmActivityController::class,
                                'eWarungs',
                            ]
                        );

                        Route::get(
                            '/pending-participants',
                            [
                                SurveyorKpmActivityController::class,
                                'pending',
                            ]
                        );

                        Route::post(
                            '/transactions',
                            [
                                SurveyorKpmActivityController::class,
                                'storeTransaction',
                            ]
                        );

                        Route::post(
                            '/kpm-verifications',
                            [
                                SurveyorKpmActivityController::class,
                                'storeVerification',
                            ]
                        );

                        Route::get(
                            '/activity-history',
                            [
                                SurveyorKpmActivityController::class,
                                'history',
                            ]
                        );
                    });

                Route::middleware('role:manager')
                    ->prefix('manager')
                    ->group(function (): void {
                        Route::get(
                            '/kpm-verifications',
                            [
                                ManagerKpmVerificationController::class,
                                'index',
                            ]
                        );

                        Route::put(
                            '/kpm-verifications/{verification}/cancel',
                            [
                                ManagerKpmVerificationController::class,
                                'cancel',
                            ]
                        )->whereNumber('verification');
                    });
            });
    }
}