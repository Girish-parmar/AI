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

// Retention pruning for the two monotonic tables (audit_logs via
// AuditLog::prunable(), notifications via the dedicated command) plus a
// weekly measurement of the database against the hosting plan's hard
// 3 GB per-database cap. Windows and thresholds live in config/hosting.php.
Schedule::command('model:prune')->dailyAt('03:00');
Schedule::command('notifications:prune')->dailyAt('03:10');
Schedule::command('hosting:health')->weeklyOn(1, '03:30');
