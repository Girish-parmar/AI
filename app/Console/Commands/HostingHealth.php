<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\DatabaseSizeWarning;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Number;

/**
 * Measures the database against the hosting plan's hard per-database
 * cap (3 GB on the production target — see config/hosting.php) and
 * notifies SuperAdmins when the audit's warning (70%) or migration
 * (85%) threshold is crossed. Shared hosting gives no server-side
 * alerting, so the app has to watch its own ceiling.
 */
class HostingHealth extends Command
{
    protected $signature = 'hosting:health';

    protected $description = "Check database size against the hosting plan's cap and alert on threshold breaches";

    public function handle(): int
    {
        $size = $this->databaseSizeBytes();

        if ($size === null) {
            $this->warn('Database size not measurable on connection: '.config('database.default'));

            return self::SUCCESS;
        }

        $cap = (int) config('hosting.database_cap_bytes');
        $ratio = $cap > 0 ? $size / $cap : 0;
        $percent = (int) round($ratio * 100);

        $sizeLabel = Number::fileSize($size, precision: 1);
        $capLabel = Number::fileSize($cap, precision: 1);
        $this->info("Database: {$sizeLabel} of {$capLabel} cap ({$percent}%).");

        $backups = $this->backupsSizeBytes();
        $this->info('Local backups: '.Number::fileSize($backups, precision: 1).' in storage/app/backups.');

        $critical = $ratio >= (float) config('hosting.database_critical_ratio');
        $warn = $ratio >= (float) config('hosting.database_warn_ratio');

        if ($warn || $critical) {
            $superadmins = User::where('role', Role::SuperAdmin)->get();
            Notification::send($superadmins, new DatabaseSizeWarning($sizeLabel, $capLabel, $percent, $critical));

            $this->warn($critical
                ? 'CRITICAL: past the migration threshold — SuperAdmins notified.'
                : 'Warning threshold crossed — SuperAdmins notified.');
        }

        return self::SUCCESS;
    }

    private function databaseSizeBytes(): ?int
    {
        return match (config('database.default')) {
            // page_count * page_size works for file-backed and in-memory
            // databases alike, unlike stat()ing the database file.
            'sqlite' => DB::selectOne('SELECT page_count * page_size AS size FROM pragma_page_count(), pragma_page_size()')->size,
            'mysql' => (int) DB::selectOne(
                'SELECT COALESCE(SUM(data_length + index_length), 0) AS size FROM information_schema.tables WHERE table_schema = DATABASE()'
            )->size,
            default => null,
        };
    }

    private function backupsSizeBytes(): int
    {
        $directory = storage_path('app/backups');

        if (! File::isDirectory($directory)) {
            return 0;
        }

        return array_sum(array_map(fn ($file) => $file->getSize(), File::files($directory)));
    }
}
