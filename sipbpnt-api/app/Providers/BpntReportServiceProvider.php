<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\BpntReportRepositoryInterface;
use App\Http\Controllers\Api\V1\Report\BpntReportController;
use App\Repositories\EloquentBpntReportRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class BpntReportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            BpntReportRepositoryInterface::class,
            EloquentBpntReportRepository::class
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
                Route::middleware(
                    'role:manager,admin_dinsos,kepala_dinas'
                )->group(function (): void {
                    Route::get(
                        '/reports',
                        [
                            BpntReportController::class,
                            'index',
                        ]
                    );

                    Route::get(
                        '/reports/{period}',
                        [
                            BpntReportController::class,
                            'show',
                        ]
                    )->whereNumber('period');

                    Route::get(
                        '/reports/{period}/excel',
                        [
                            BpntReportController::class,
                            'excel',
                        ]
                    )->whereNumber('period');
                });

                Route::middleware('role:manager')
                    ->post(
                        '/manager/reports/{period}/finalize',
                        [
                            BpntReportController::class,
                            'finalize',
                        ]
                    )
                    ->whereNumber('period');
            });
    }
}