<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\BnbaImportRepositoryInterface;
use App\Contracts\Repositories\BpntParticipantRepositoryInterface;
use App\Contracts\Repositories\BpntPeriodRepositoryInterface;
use App\Contracts\Repositories\KpmRepositoryInterface;
use App\Contracts\Repositories\SurveyorRepositoryInterface;
use App\Contracts\Repositories\WilayahRepositoryInterface;
use App\Repositories\EloquentAuditLogRepository;
use App\Repositories\EloquentBnbaImportRepository;
use App\Repositories\EloquentBpntParticipantRepository;
use App\Repositories\EloquentBpntPeriodRepository;
use App\Repositories\EloquentKpmRepository;
use App\Repositories\EloquentSurveyorRepository;
use App\Repositories\EloquentWilayahRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider
    extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            BpntPeriodRepositoryInterface::class,
            EloquentBpntPeriodRepository::class
        );

        $this->app->bind(
            BnbaImportRepositoryInterface::class,
            EloquentBnbaImportRepository::class
        );

        $this->app->bind(
            KpmRepositoryInterface::class,
            EloquentKpmRepository::class
        );

        $this->app->bind(
            BpntParticipantRepositoryInterface::class,
            EloquentBpntParticipantRepository::class
        );

        $this->app->bind(
            AuditLogRepositoryInterface::class,
            EloquentAuditLogRepository::class
        );

        $this->app->bind(
            WilayahRepositoryInterface::class,
            EloquentWilayahRepository::class
        );

        $this->app->bind(
            SurveyorRepositoryInterface::class,
            EloquentSurveyorRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}