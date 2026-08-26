<?php

use App\Providers\AppServiceProvider;
use App\Providers\KpmActivityServiceProvider;
use App\Providers\ManagerTransactionMonitoringServiceProvider;

return [
    AppServiceProvider::class,
    KpmActivityServiceProvider::class,
    ManagerTransactionMonitoringServiceProvider::class,
];