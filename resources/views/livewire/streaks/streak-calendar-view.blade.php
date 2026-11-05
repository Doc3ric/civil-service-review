<div class="space-y-8">
    <!-- Streak Header & Counters -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Current Streak -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Current Streak</span>
                <h3 class="text-3xl font-extrabold text-[#C92535] mt-1">{{ $streaks['current_streak'] }} <span class="text-sm font-semibold text-slate-500">Consecutive Days</span></h3>
                <p class="text-[11px] text-slate-400 mt-1">Asia/Manila Timezone</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-[#C92535] flex items-center justify-center">
                <i data-lucide="flame" class="w-7 h-7"></i>
            </div>
        </div>

        <!-- Longest Streak -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Longest Streak</span>
                <h3 class="text-3xl font-extrabold text-slate-900 mt-1">{{ $streaks['longest_streak'] }} <span class="text-sm font-semibold text-slate-500">Days</span></h3>
                <p class="text-[11px] text-slate-400 mt-1">Personal Record</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <i data-lucide="award" class="w-7 h-7"></i>
            </div>
        </div>

        <!-- Total Study Days -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Total Active Days</span>
                <h3 class="text-3xl font-extrabold text-[#174A8B] mt-1">{{ $streaks['total_study_days'] }} <span class="text-sm font-semibold text-slate-500">Days</span></h3>
                <p class="text-[11px] text-slate-400 mt-1">Recorded Study Sessions</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-[#174A8B] flex items-center justify-center">
                <i data-lucide="calendar-days" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Today's Target -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Daily Goal Target</span>
                <h3 class="text-3xl font-extrabold text-slate-900 mt-1">{{ $streaks['today_questions_count'] }} <span class="text-sm font-semibold text-slate-400">/ {{ $streaks['daily_target'] }}</span></h3>
                <p class="text-[11px] font-semibold {{ $streaks['daily_target_met'] ? 'text-emerald-600' : 'text-amber-600' }} mt-1">
                    {{ $streaks['daily_target_met'] ? 'Goal Achieved Today!' : 'Solve ' . max(0, $streaks['daily_target'] - $streaks['today_questions_count']) . ' more' }}
                </p>
            </div>
            <div class="w-12 h-12 rounded-xl {{ $streaks['daily_target_met'] ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center">
                <i data-lucide="{{ $streaks['daily_target_met'] ? 'check-circle-2' : 'clock' }}" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Monthly Calendar Heatmap -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden space-y-6">
        <!-- Calendar Navigation Header -->
        <div class="p-6 border-b border-slate-200 bg-slate-50/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h3 class="text-xl font-extrabold text-slate-900">{{ $calendar['month_name'] }}</h3>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-blue-100 text-[#174A8B]">
                        {{ $calendar['month_study_days'] }} Active Days
                    </span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-200 text-slate-700">
                        {{ $calendar['month_total_questions'] }} Questions Solved
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">Daily consistency activity map</p>
            </div>

            <div class="flex items-center gap-2">
                <button wire:click="previousMonth"
                        class="p-2 rounded-xl text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-2xs">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                </button>
                <button wire:click="goToCurrentMonth"
                        class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-2xs">
                    Today
                </button>
                <button wire:click="nextMonth"
                        class="p-2 rounded-xl text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-2xs">
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- Heatmap Grid -->
        <div class="p-6">
            <!-- Day of Week Headers -->
            <div class="grid grid-cols-7 gap-2 mb-2 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">
                <div>Sun</div>
                <div>Mon</div>
                <div>Tue</div>
                <div>Wed</div>
                <div>Thu</div>
                <div>Fri</div>
                <div>Sat</div>
            </div>

            <!-- Calendar Days -->
            @php
                $firstDayOfWeek = $calendar['days'][0]['day_of_week'] ?? 0;
            @endphp
            <div class="grid grid-cols-7 gap-2">
                <!-- Empty padding cells before first day -->
                @for ($p = 0; $p < $firstDayOfWeek; $p++)
                    <div class="h-20 sm:h-24 rounded-xl bg-slate-50/40 border border-transparent"></div>
                @endfor

                <!-- Month Days -->
                @foreach ($calendar['days'] as $day)
                    <div class="h-20 sm:h-24 p-2 sm:p-2.5 rounded-xl border transition flex flex-col justify-between relative {{ $day['is_today'] ? 'ring-2 ring-[#C92535] border-[#C92535]' : 'border-slate-200' }} {{ $day['has_activity'] ? 'bg-blue-50/70 border-blue-300' : 'bg-slate-50/30' }}">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold {{ $day['is_today'] ? 'text-[#C92535] font-extrabold' : ($day['has_activity'] ? 'text-[#174A8B]' : 'text-slate-500') }}">
                                {{ $day['day_number'] }}
                            </span>
                            @if ($day['is_today'])
                                <span class="text-[9px] font-bold text-white bg-[#C92535] px-1.5 py-0.2 rounded-full uppercase">
                                    Today
                                </span>
                            @endif
                        </div>

                        <!-- Activity Indicator -->
                        @if ($day['has_activity'])
                            <div class="space-y-0.5">
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-[#174A8B]">
                                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-blue-600"></i>
                                    <span>{{ $day['questions_count'] }} Qs</span>
                                </span>
                                <span class="text-[10px] text-slate-400 block truncate">
                                    {{ $day['activities_count'] }} {{ Str::plural('test', $day['activities_count']) }}
                                </span>
                            </div>
                        @else
                            <div class="text-[10px] text-slate-300 italic">
                                No activity
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Legend Footer -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between text-xs text-slate-500 gap-4">
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-2">
                    <span class="w-3.5 h-3.5 rounded bg-blue-100 border border-blue-400"></span>
                    <span>Completed Study Session</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3.5 h-3.5 rounded bg-white border-2 border-[#C92535]"></span>
                    <span>Current Day (Today)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3.5 h-3.5 rounded bg-slate-50 border border-slate-200"></span>
                    <span>No Activity</span>
                </div>
            </div>

            <div class="text-[11px] text-slate-400">
                Qualifying activities include completed 150 mock examinations and category practice sessions.
            </div>
        </div>
    </div>
</div>
