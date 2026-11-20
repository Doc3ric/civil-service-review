<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Services\Backup\DatabaseBackupService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use RuntimeException;

class DataBackupManager extends Component
{
    public ?string $backupToRestore = null;
    public string $restoreConfirmation = '';
    public bool $showRestoreModal = false;

    public function createBackup(DatabaseBackupService $backupService): void
    {
        try {
            $path = $backupService->createBackup();
            if ($path === null) {
                throw new RuntimeException('A file-based database backup is not available for the current database connection.');
            }

            session()->flash('success', 'Database backup created: ' . basename($path));
        } catch (RuntimeException $exception) {
            $this->addError('backup', $exception->getMessage());
        }
    }

    public function requestRestore(string $backupName): void
    {
        $available = collect(app(DatabaseBackupService::class)->availableSqliteBackups())->pluck('name')->all();
        if (!in_array($backupName, $available, true)) {
            $this->addError('backup', 'That backup is no longer available. Refresh and choose another file.');
            return;
        }

        $this->backupToRestore = $backupName;
        $this->restoreConfirmation = '';
        $this->resetValidation();
        $this->showRestoreModal = true;
    }

    public function cancelRestore(): void
    {
        $this->backupToRestore = null;
        $this->restoreConfirmation = '';
        $this->showRestoreModal = false;
    }

    public function restoreBackup(DatabaseBackupService $backupService): void
    {
        $this->validate([
            'backupToRestore' => ['required', 'string'],
            'restoreConfirmation' => ['required', 'in:RESTORE'],
        ]);

        try {
            $backupService->restoreSqliteBackup($this->backupToRestore);
            $this->cancelRestore();
            session()->flash('success', 'Database restored. A backup of the database from before restoration was saved first.');
        } catch (RuntimeException $exception) {
            $this->addError('backup', $exception->getMessage());
        }
    }

    public function render(DatabaseBackupService $backupService): View
    {
        return view('livewire.settings.data-backup-manager', [
            'backups' => $backupService->availableSqliteBackups(),
            'isSqlite' => config('database.default') === 'sqlite',
        ]);
    }
}
