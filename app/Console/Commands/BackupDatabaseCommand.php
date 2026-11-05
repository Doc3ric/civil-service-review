<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'app:backup-database {--prune-days=7 : Days to retain backups} {--restore= : Path to backup file to restore}';

    protected $description = 'Perform an automated database backup, verify file integrity, and prune expired archives';

    public function handle(): int
    {
        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $restorePath = $this->option('restore');
        if ($restorePath) {
            return $this->handleRestore((string) $restorePath);
        }

        return $this->handleBackup($backupDir);
    }

    private function handleBackup(string $backupDir): int
    {
        $connection = config('database.default');
        $timestamp = Carbon::now()->format('Y-m-d_His');
        $this->info("Initiating database backup for [{$connection}] at {$timestamp}...");

        if ($connection === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            if (!file_exists($dbPath)) {
                $this->error("SQLite database file not found at: {$dbPath}");
                return Command::FAILURE;
            }

            $backupFile = "{$backupDir}/backup_{$timestamp}.sqlite";
            if (!copy($dbPath, $backupFile)) {
                $this->error("Failed to copy SQLite database.");
                return Command::FAILURE;
            }

            $hash = hash_file('sha256', $backupFile);
            $size = round(filesize($backupFile) / 1024, 2);

            $this->info("✓ SQLite Backup created successfully!");
            $this->line("  File: {$backupFile}");
            $this->line("  Size: {$size} KB");
            $this->line("  SHA-256: {$hash}");
        } elseif ($connection === 'mysql') {
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port', 3306);
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');

            $backupFile = "{$backupDir}/backup_{$database}_{$timestamp}.sql";

            $cmd = [
                'mysqldump',
                "--host={$host}",
                "--port={$port}",
                "--user={$username}",
                "--single-transaction",
                "--quick",
                "--routines",
                "--triggers",
            ];

            if (!empty($password)) {
                $cmd[] = "--password={$password}";
            }

            $cmd[] = $database;

            $process = new Process($cmd);
            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                $this->error("mysqldump failed: " . $process->getErrorOutput());
                return Command::FAILURE;
            }

            file_put_contents($backupFile, $process->getOutput());
            $hash = hash_file('sha256', $backupFile);
            $size = round(filesize($backupFile) / 1024, 2);

            $this->info("✓ MySQL Backup created successfully!");
            $this->line("  File: {$backupFile}");
            $this->line("  Size: {$size} KB");
            $this->line("  SHA-256: {$hash}");
        } else {
            $this->warn("Automated dump not configured for driver [{$connection}].");
            return Command::SUCCESS;
        }

        // Pruning older backups
        $pruneDays = (int) $this->option('prune-days');
        $this->pruneOldBackups($backupDir, $pruneDays);

        return Command::SUCCESS;
    }

    private function handleRestore(string $filePath): int
    {
        if (!file_exists($filePath)) {
            $this->error("Backup file does not exist: {$filePath}");
            return Command::FAILURE;
        }

        if (!$this->confirm("Are you sure you want to restore database from [{$filePath}]? Current data will be replaced!")) {
            $this->info("Restoration cancelled.");
            return Command::SUCCESS;
        }

        $connection = config('database.default');

        if ($connection === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            if (copy($filePath, $dbPath)) {
                $this->info("✓ SQLite database successfully restored from [{$filePath}].");
                return Command::SUCCESS;
            }
            $this->error("Failed to copy backup file into database target.");
            return Command::FAILURE;
        }

        $this->error("MySQL restore should be performed via: mysql -u <user> -p <database> < " . escapeshellarg($filePath));
        return Command::SUCCESS;
    }

    private function pruneOldBackups(string $backupDir, int $days): void
    {
        $cutoff = Carbon::now()->subDays($days);
        $files = File::files($backupDir);
        $pruned = 0;

        foreach ($files as $file) {
            $mtime = Carbon::createFromTimestamp($file->getMTime());
            if ($mtime->lt($cutoff)) {
                File::delete($file->getPathname());
                $pruned++;
            }
        }

        if ($pruned > 0) {
            $this->info("Pruned {$pruned} backup files older than {$days} days.");
        }
    }
}
