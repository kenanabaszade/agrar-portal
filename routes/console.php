<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule training reminders to run every hour
Schedule::command('training:send-reminders')->hourly();

// Schedule expired certificate update to run daily at midnight
Schedule::command('certificates:update-expired')->daily();
