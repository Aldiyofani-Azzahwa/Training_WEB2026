<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\ManagerTransactionMonitoringRepositoryInterface;
use App\Http\Controllers\Api\V1\Manager\ManagerTransactionMonitoringController;
use App\Repositories\EloquentManagerTransactionMonitoringRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class ManagerTransactionMonitoringServiceProvider
    extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ManagerTransactionMonitoringRepositoryInterface::class,
            EloquentManagerTransactionMonitoringRepository::class
        );
    }

    public function boot(): void
    {
        Route::prefix('api/v1/manager')
            ->middleware([
                'api',
                'auth:sanctum',
                'active.user',
                'role:manager',
            ])
            ->group(function (): void {
                Route::get(
                    '/transaction-monitoring',
                    [
                        ManagerTransactionMonitoringController::class,
                        'index',
                    ]
                );
            });
    }
}