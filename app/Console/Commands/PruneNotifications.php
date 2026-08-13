<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Deletes old rows from the notifications table: read notifications
 * past the retention window, and unread ones past twice that window
 * (an unread notification nobody opened in six months is noise, not
 * signal). Keeps another monotonic table inside the hosting plan's
 * 3 GB per-database cap; see config/hosting.php.
 */
class PruneNotifications extends Command
{
    protected $signature = 'notifications:prune';

    protected $description = 'Delete old read notifications and ancient unread ones';

    public function handle(): int
    {
        $days = (int) config('hosting.notification_retention_days');

        $read = DatabaseNotification::whereNotNull('read_at')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $unread = DatabaseNotification::whereNull('read_at')
            ->where('created_at', '<', now()->subDays($days * 2))
            ->delete();

        $this->info("Pruned {$read} read and {$unread} unread notifications.");

        return self::SUCCESS;
    }
}
