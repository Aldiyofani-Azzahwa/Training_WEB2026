<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\SurveyorMonitoringReportRepositoryInterface;
use App\Http\Controllers\Api\V1\Surveyor\SurveyorMonitoringReportController;
use App\Repositories\EloquentSurveyorMonitoringReportRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class SurveyorMonitoringReportServiceProvider
    extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SurveyorMonitoringReportRepositoryInterface::class,
            EloquentSurveyorMonitoringReportRepository::class
        );
    }

    public function boot(): void
    {
        Route::prefix(
            'api/v1/surveyor'
        )
            ->middleware([
                'api',
                'auth:sanctum',
                'active.user',
                'role:surveyor',
            ])
            ->group(
                function (): void {
                    Route::get(
                        '/monitoring-report',
                        [
                            SurveyorMonitoringReportController::class,
                            'show',
                        ]
                    );

                    Route::put(
                        '/monitoring-report',
                        [
                            SurveyorMonitoringReportController::class,
                            'update',
                        ]
                    );

                    Route::get(
                        '/monitoring-report/pdf',
                        [
                            SurveyorMonitoringReportController::class,
                            'pdf',
                        ]
                    );
                }
            );
    }
}