<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Runs off the same `* * * * * php artisan schedule:run` cron entry the
// deployment guide sets up for everything else — no separate cron job
// needed. Keeps 14 days of backups by default (see --keep on the command).
Schedule::command('backup:database')->dailyAt('02:30');
