<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\BpntReportServiceProvider;
use App\Providers\KpmActivityServiceProvider;
use App\Providers\ManagerTransactionMonitoringServiceProvider;

return [
    AppServiceProvider::class,
    BpntReportServiceProvider::class,
    KpmActivityServiceProvider::class,
    ManagerTransactionMonitoringServiceProvider::class,
];