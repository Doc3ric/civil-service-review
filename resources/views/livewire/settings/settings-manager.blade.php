<div class="max-w-4xl mx-auto space-y-6">
    <!-- Tab Controls -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3">
        <button wire:click="$set('activeTab', 'exam')"
                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 {{ $activeTab === 'exam' ? 'bg-[#174A8B] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
            <i data-lucide="graduation-cap" class="w-4 h-4"></i>
            <span>Examination Blueprint</span>
        </button>
        <button wire:click="$set('activeTab', 'study')"
                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 {{ $activeTab === 'study' ? 'bg-[#174A8B] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
            <i data-lucide="target" class="w-4 h-4"></i>
            <span>Study Goals & Timezone</span>
        </button>
        <button wire:click="$set('activeTab', 'profile')"
                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 {{ $activeTab === 'profile' ? 'bg-[#174A8B] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
            <i data-lucide="settings" class="w-4 h-4"></i>
            <span>Account Profile</span>
        </button>
    </div>

    <!-- Examination Settings Tab -->
    @if ($activeTab === 'exam')
        <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-xs space-y-6">
            <div>
                <h3 class="text-base font-bold text-slate-900">Configurable Examination Blueprint</h3>
                <p class="text-xs text-slate-500">
                    Adjust standard practice examination parameters and category distribution allocations.
                </p>
            </div>

            <form wire:submit="saveExamSettings" class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Blueprint Title</label>
                        <input type="text" wire:model="examName" class="block w-full py-2 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                        @error('examName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Total Examination Questions</label>
                        <input type="number" wire:model="totalQuestions" class="block w-full py-2 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                        @error('totalQuestions') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Duration (Minutes)</label>
                        <input type="number" wire:model="durationMinutes" class="block w-full py-2 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                        @error('durationMinutes') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Passing Target Percentage (%)</label>
                        <input type="number" step="0.01" wire:model="passingPercentage" class="block w-full py-2 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                        @error('passingPercentage') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Category Distribution -->
                <div class="space-y-3 pt-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Category Question Distribution (Must Equal {{ $totalQuestions }} Items)</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($categories as $cat)
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900">{{ $cat->name }}</span>
                                    <span class="text-[11px] font-semibold text-slate-500 uppercase">{{ $cat->code instanceof \BackedEnum ? $cat->code->value : $cat->code }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="number" wire:model="categoryAllocations.{{ $cat->id }}" min="0"
                                           class="w-full py-1.5 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                                    <span class="text-xs text-slate-500 font-medium">items</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl font-bold text-xs text-white bg-[#C92535] hover:bg-[#A81B29] transition shadow-xs">
                        Save Blueprint Configuration
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Study Consistency Settings Tab -->
    @if ($activeTab === 'study')
        <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-xs space-y-6">
            <div>
                <h3 class="text-base font-bold text-slate-900">Study Consistency Preferences</h3>
                <p class="text-xs text-slate-500">
                    Configure daily practice quotas and timezone for streak continuity tracking.
                </p>
            </div>

            <form wire:submit="saveStudySettings" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Daily Question Target</label>
                        <input type="number" wire:model="dailyQuestionTarget" min="1" max="500"
                               class="block w-full py-2 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                        <p class="text-[11px] text-slate-400 mt-1">Number of questions required each day to meet study streak goal.</p>
                        @error('dailyQuestionTarget') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Daily Study Time Goal (Minutes)</label>
                        <input type="number" wire:model="dailyStudyGoalMinutes" min="5" max="600"
                               class="block w-full py-2 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                        <p class="text-[11px] text-slate-400 mt-1">Recommended daily focus time.</p>
                        @error('dailyStudyGoalMinutes') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Timezone</label>
                        <input type="text" wire:model="timezone" value="Asia/Manila" readonly
                               class="block w-full py-2 px-3 border border-slate-300 bg-slate-50 rounded-lg text-xs text-slate-700 font-mono">
                        <p class="text-[11px] text-slate-400 mt-1">Official CSC Philippine examination standard: Asia/Manila (UTC+8).</p>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl font-bold text-xs text-white bg-[#174A8B] hover:bg-blue-900 transition shadow-xs">
                        Save Study Settings
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Profile Settings Tab -->
    @if ($activeTab === 'profile')
        <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-xs space-y-6">
            <div>
                <h3 class="text-base font-bold text-slate-900">Reviewer Account Information</h3>
                <p class="text-xs text-slate-500">
                    Update your account credentials and personal profile details.
                </p>
            </div>

            <form wire:submit="saveProfileSettings" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Name</label>
                        <input type="text" wire:model="userName" required
                               class="block w-full py-2 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                        @error('userName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Email Address</label>
                        <input type="email" wire:model="userEmail" required
                               class="block w-full py-2 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                        @error('userEmail') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Password Change Section -->
                <div class="pt-4 border-t border-slate-100 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Change Password (Leave blank to keep current)</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Current Password</label>
                            <input type="password" wire:model="currentPassword"
                                   class="block w-full py-2 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                            @error('currentPassword') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">New Password</label>
                            <input type="password" wire:model="newPassword"
                                   class="block w-full py-2 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                            @error('newPassword') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Confirm New Password</label>
                            <input type="password" wire:model="newPasswordConfirmation"
                                   class="block w-full py-2 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                            @error('newPasswordConfirmation') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl font-bold text-xs text-white bg-[#174A8B] hover:bg-blue-900 transition shadow-xs">
                        Update Account Profile
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
