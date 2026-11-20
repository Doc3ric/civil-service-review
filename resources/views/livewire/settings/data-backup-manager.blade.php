<div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-xs space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-base font-bold text-slate-900">Question Bank Backups</h3>
            <p class="text-xs text-slate-500 mt-1">Create a dated database copy and restore it if you need to roll back an import or deletion.</p>
        </div>
        <button type="button" wire:click="createBackup" wire:loading.attr="disabled"
                class="px-4 py-2.5 rounded-xl font-bold text-xs text-white bg-[#174A8B] hover:bg-blue-900 disabled:opacity-50">
            Create Backup Now
        </button>
    </div>

    @if (session()->has('success'))
        <div role="status" class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-xs text-emerald-800">{{ session('success') }}</div>
    @endif
    @error('backup')
        <div role="alert" class="p-3 rounded-lg bg-rose-50 border border-rose-200 text-xs text-rose-800">{{ $message }}</div>
    @enderror

    @if (!$isSqlite)
        <p class="p-4 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-900">The current database supports backup export, but in-app restore is currently available only for SQLite.</p>
    @elseif ($backups === [])
        <p class="p-6 rounded-xl bg-slate-50 border border-slate-200 text-center text-xs text-slate-500">No backups found yet. Create one before importing a large question set.</p>
    @else
        <div class="overflow-x-auto border border-slate-200 rounded-xl">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider font-semibold">
                    <tr>
                        <th class="px-4 py-3">Backup File</th>
                        <th class="px-4 py-3">Created</th>
                        <th class="px-4 py-3">Size</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($backups as $backup)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $backup['name'] }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ date('M d, Y h:i A', $backup['created_at']) }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ number_format($backup['size'] / 1024, 1) }} KB</td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" wire:click="requestRestore('{{ $backup['name'] }}')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200">
                                    Restore
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($showRestoreModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="restore-backup-title">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-900/60" wire:click="cancelRestore"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full z-10 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 space-y-2">
                        <h3 id="restore-backup-title" class="text-sm font-bold text-slate-900">Restore {{ $backupToRestore }}?</h3>
                        <p class="text-xs text-slate-600">This replaces current questions, accounts, settings, and exam history with the selected backup. The current database will be backed up first. Type RESTORE to continue.</p>
                        <input type="text" wire:model="restoreConfirmation" autocomplete="off" placeholder="Type RESTORE"
                               class="block w-full py-2 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-rose-500">
                        @error('restoreConfirmation') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        @error('backup') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-end gap-3">
                        <button type="button" wire:click="cancelRestore" class="px-4 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-300">Cancel</button>
                        <button type="button" wire:click="restoreBackup" wire:loading.attr="disabled"
                                class="px-5 py-2 rounded-lg text-xs font-bold text-white bg-rose-700 hover:bg-rose-800 disabled:opacity-50">Restore Database</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
