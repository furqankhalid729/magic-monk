<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CheckLocationOffer;
use App\Jobs\ProcessOrderReminder;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CheckLocationOffer)->everySixHours();
Schedule::job(new ProcessOrderReminder)->everyMinute();
