<?php

declare(strict_types=1);

namespace App\Services\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use PDO;
use RuntimeException;
use Throwable;

class DatabaseBackupService
{
    public function createBeforeBulkChange(): void
    {
        if (config('database.default') === 'sqlite' && config('database.connections.sqlite.database') === ':memory:') {
            return;
        }

        if ($this->createBackup() === null) {
            throw new RuntimeException('A database backup could not be created. No questions were changed.');
        }
    }

    public function createBackup(): ?string
    {
        if (config('database.default') === 'sqlite' && config('database.connections.sqlite.database') === ':memory:') {
            return null;
        }

        $exitCode = Artisan::call('app:backup-database', ['--prune-days' => 3650]);
        if ($exitCode !== 0) {
            return null;
        }

        $output = Artisan::output();
        if (preg_match('/File:\s*(.+)/', $output, $matches) !== 1) {
            return null;
        }

        return trim($matches[1]);
    }

    /** @return array<int, array{name: string, size: int, created_at: int}> */
    public function availableSqliteBackups(): array
    {
        if (config('database.default') !== 'sqlite') {
            return [];
        }

        $backupDirectory = storage_path('app/backups');
        if (!is_dir($backupDirectory)) {
            return [];
        }

        $backups = [];
        foreach (glob($backupDirectory . DIRECTORY_SEPARATOR . 'backup_*.sqlite') ?: [] as $path) {
            $backupDb = null;
            try {
                $backupDb = new PDO('sqlite:file:' . str_replace('\\', '/', $path) . '?mode=ro');
                $this->validateSqliteBackup($backupDb);
            } catch (Throwable) {
                continue;
            } finally {
                $backupDb = null;
            }

            $backups[] = [
                'name' => basename($path),
                'size' => (int) filesize($path),
                'created_at' => (int) filemtime($path),
            ];
        }

        usort($backups, fn (array $left, array $right) => $right['created_at'] <=> $left['created_at']);

        return $backups;
    }

    public function restoreSqliteBackup(string $backupName): void
    {
        if (config('database.default') !== 'sqlite') {
            throw new RuntimeException('In-app restore is currently available for SQLite databases only.');
        }

        if (basename($backupName) !== $backupName || preg_match('/^backup_[A-Za-z0-9_-]+\.sqlite$/', $backupName) !== 1) {
            throw new RuntimeException('Choose a valid backup file.');
        }

        $backupPath = storage_path('app/backups/' . $backupName);
        $backupDirectory = realpath(storage_path('app/backups'));
        $resolvedBackupPath = realpath($backupPath);
        if ($backupDirectory === false || $resolvedBackupPath === false || !str_starts_with($resolvedBackupPath, $backupDirectory . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('The selected backup file was not found.');
        }

        $backupDb = new PDO('sqlite:file:' . str_replace('\\', '/', $resolvedBackupPath) . '?mode=ro');
        try {
            $this->validateSqliteBackup($backupDb);
        } finally {
            $backupDb = null;
        }

        $currentBackupPath = $this->createBackup();
        if ($currentBackupPath === null) {
            throw new RuntimeException('A backup of the current database could not be created. Restore was cancelled.');
        }

        $databasePath = config('database.connections.sqlite.database');
        DB::disconnect();
        if (!copy($resolvedBackupPath, $databasePath)) {
            DB::purge();
            throw new RuntimeException('The selected backup could not be copied to the database.');
        }
        DB::purge();

        try {
            $exitCode = Artisan::call('migrate', ['--force' => true]);
            if ($exitCode !== 0) {
                throw new RuntimeException('The restored database could not be migrated to the current application version.');
            }
        } catch (Throwable $exception) {
            DB::disconnect();
            copy($currentBackupPath, $databasePath);
            DB::purge();
            throw new RuntimeException('Restore failed. The database was returned to its pre-restore state.', previous: $exception);
        }
    }

    private function validateSqliteBackup(PDO $backupDb): void
    {
        if ($backupDb->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
            throw new RuntimeException('The selected backup file failed its integrity check.');
        }

        $questionCount = (int) $backupDb->query('SELECT COUNT(*) FROM questions')->fetchColumn();
        $choiceCount = (int) $backupDb->query('SELECT COUNT(*) FROM question_choices')->fetchColumn();
        if ($questionCount > 0 && $choiceCount === 0) {
            throw new RuntimeException('This backup has questions but no answer choices, so it cannot safely be restored.');
        }

        $incompleteQuestionCount = (int) $backupDb->query(
            'SELECT COUNT(*) FROM questions q WHERE q.deleted_at IS NULL AND (SELECT COUNT(*) FROM question_choices c WHERE c.question_id = q.id) <> 4'
        )->fetchColumn();
        if ($incompleteQuestionCount > 0) {
            throw new RuntimeException('This backup contains questions without exactly four answer choices.');
        }
    }
}
