<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\HeadOfficeDashboardRepositoryInterface;
use App\Http\Controllers\Api\V1\HeadOffice\HeadOfficeDashboardController;
use App\Repositories\EloquentHeadOfficeDashboardRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class HeadOfficeDashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            HeadOfficeDashboardRepositoryInterface::class,
            EloquentHeadOfficeDashboardRepository::class
        );
    }

    public function boot(): void
    {
        Route::prefix('api/v1/head-office')
            ->middleware([
                'api',
                'auth:sanctum',
                'active.user',
                'role:kepala_dinas',
            ])
            ->group(function (): void {
                Route::get(
                    '/dashboard',
                    [
                        HeadOfficeDashboardController::class,
                        'show',
                    ]
                );
            });
    }
}