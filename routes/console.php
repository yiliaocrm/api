<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:customer-group-computing-command')->dailyAt('00:01');
Schedule::command('app:cleanup-export-files-command')->dailyAt('01:00');
Schedule::command('app:customer-product-expired-command')->dailyAt('01:05');
Schedule::command('app:update-customer-age-command')->dailyAt('02:00');
