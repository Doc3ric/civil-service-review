<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Services\Backup\DatabaseBackupService;
use PDO;
use RuntimeException;
use Tests\TestCase;

class DatabaseBackupServiceTest extends TestCase
{
    public function test_restore_rejects_a_backup_with_questions_but_no_answer_choices(): void
    {
        $backupDirectory = storage_path('app/backups');
        if (!is_dir($backupDirectory)) {
            mkdir($backupDirectory, 0755, true);
        }
        $backupName = 'backup_test_missing_choices_' . bin2hex(random_bytes(4)) . '.sqlite';
        $backupPath = $backupDirectory . DIRECTORY_SEPARATOR . $backupName;
        register_shutdown_function(static function () use ($backupPath): void {
            if (file_exists($backupPath)) {
                @unlink($backupPath);
            }
        });
        $database = new PDO('sqlite:' . $backupPath);
        $database->exec('CREATE TABLE questions (id INTEGER PRIMARY KEY, deleted_at TEXT NULL)');
        $database->exec('CREATE TABLE question_choices (id INTEGER PRIMARY KEY, question_id INTEGER NOT NULL)');
        $database->exec('INSERT INTO questions (id, deleted_at) VALUES (1, NULL)');
        $database = null;

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('questions but no answer choices');

            app(DatabaseBackupService::class)->restoreSqliteBackup($backupName);
        } finally {
            if (file_exists($backupPath)) {
                @unlink($backupPath);
            }
        }
    }
}
