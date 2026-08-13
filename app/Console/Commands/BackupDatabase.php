<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Dumps and gzips the database to storage/app/backups, then prunes
 * anything older than --keep days. Supports the two connections this
 * app actually uses (sqlite locally, mysql in production); anything
 * else is refused rather than silently doing nothing.
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--keep=14 : Number of days of backups to retain}';

    protected $description = 'Back up the application database and prune old backups';

    public function handle(): int
    {
        $directory = storage_path('app/backups');
        File::ensureDirectoryExists($directory);

        $connection = config('database.default');
        $timestamp = now()->format('Y-m-d_His');

        $destination = match ($connection) {
            'sqlite' => $this->backupSqlite($directory, $timestamp),
            'mysql' => $this->backupMysql($directory, $timestamp),
            default => null,
        };

        if ($destination === null) {
            $this->error("Backup failed or unsupported connection: {$connection}.");

            return self::FAILURE;
        }

        $this->info("Backup written to {$destination}");

        $this->prune($directory, (int) $this->option('keep'));

        return self::SUCCESS;
    }

    private function backupSqlite(string $directory, string $timestamp): ?string
    {
        $source = config('database.connections.sqlite.database');

        if (! is_string($source) || ! is_file($source)) {
            $this->error("SQLite database file not found: {$source}");

            return null;
        }

        $destination = "{$directory}/db-{$timestamp}.sqlite.gz";
        File::put($destination, gzencode(File::get($source), 9));

        return $destination;
    }

    private function backupMysql(string $directory, string $timestamp): ?string
    {
        $config = config('database.connections.mysql');

        $process = new Process([
            'mysqldump',
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            '--single-transaction',
            '--skip-lock-tables',
            $config['database'],
            // Passed via env rather than a --password= arg so the
            // credential doesn't show up in the process list.
        ], null, ['MYSQL_PWD' => $config['password']]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error($process->getErrorOutput());

            return null;
        }

        $destination = "{$directory}/db-{$timestamp}.sql.gz";
        File::put($destination, gzencode($process->getOutput(), 9));

        return $destination;
    }

    private function prune(string $directory, int $keepDays): void
    {
        $cutoff = now()->subDays($keepDays)->getTimestamp();

        foreach (File::files($directory) as $file) {
            if ($file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
            }
        }
    }
}
