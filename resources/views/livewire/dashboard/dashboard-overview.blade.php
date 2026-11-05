<div class="space-y-8">
    <!-- Welcome & Hero Section -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#174A8B] via-[#0F3260] to-[#174A8B] text-white p-6 sm:p-8 shadow-md">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-xs font-semibold tracking-wide text-blue-200">
                    <span class="w-2 h-2 rounded-full bg-[#C92535] animate-pulse"></span>
                    <span>CSC Professional Review 2027</span>
                    <span>•</span>
                    <span>{{ $currentDate }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                    Mabuhay, {{ $user->name }}!
                </h1>
                <p class="text-sm text-blue-100 leading-relaxed">
                    {{ $dailyQuote }}
                </p>
            </div>

            <div class="flex flex-wrap sm:flex-nowrap gap-3 shrink-0">
                <a href="{{ route('exams.full') }}"
                   class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl font-bold text-sm bg-[#C92535] hover:bg-[#A81B29] text-white transition shadow-md ring-2 ring-white/20">
                    <i data-lucide="graduation-cap" class="w-5 h-5"></i>
                    <span>Take Full 150 Exam</span>
                </a>
                <a href="{{ route('exams.category') }}"
                   class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-semibold text-sm bg-white/10 hover:bg-white/20 text-white transition border border-white/20">
                    <i data-lucide="sliders-horizontal" class="w-5 h-5"></i>
                    <span>Practice Category</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Key Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Total Questions -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Question Bank</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($analytics['total_questions_in_bank']) }}</h3>
                <p class="text-xs text-blue-600 mt-1 font-medium">Published Items</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-[#174A8B] flex items-center justify-center">
                <i data-lucide="database" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Practice Exams Completed -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Exams Taken</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $analytics['total_exams_completed'] }}</h3>
                <p class="text-xs text-slate-500 mt-1 font-medium">Completed Sessions</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i data-lucide="graduation-cap" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Average Score -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Average Score</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($analytics['average_score'], 1) }}%</h3>
                <p class="text-xs {{ $analytics['average_score'] >= 80 ? 'text-emerald-600' : 'text-amber-600' }} mt-1 font-medium">
                    Passing Target: 80%
                </p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <i data-lucide="award" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Current Streak -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Study Streak</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $streaks['current_streak'] }} <span class="text-sm font-medium text-slate-500">Days</span></h3>
                <p class="text-xs text-rose-600 mt-1 font-medium">Longest: {{ $streaks['longest_streak'] }} days</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-[#C92535] flex items-center justify-center">
                <i data-lucide="flame" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Total Study Days -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Active Days</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ $streaks['total_study_days'] }} <span class="text-sm font-medium text-slate-500">Days</span></h3>
                <p class="text-xs text-slate-500 mt-1 font-medium">Recorded Activity</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <i data-lucide="calendar-days" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Examination Categories Grid (150 Blueprint Distribution) -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Examination Categories</h2>
                <p class="text-xs text-slate-500">Civil Service Professional Level 150-Item Blueprint Distribution</p>
            </div>
            <a href="{{ route('categories.index') }}" class="text-xs font-semibold text-[#174A8B] hover:underline flex items-center gap-1">
                <span>View Category Details</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach ($categoryCards as $card)
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold tracking-wide uppercase"
                                  style="background-color: {{ $card['color'] }}15; color: {{ $card['color'] }}">
                                {{ $card['code'] }}
                            </span>
                            <span class="text-xs font-semibold text-slate-500">
                                {{ $card['blueprint_percentage'] }}% of Exam
                            </span>
                        </div>

                        <h3 class="text-base font-bold text-slate-900 mb-1.5">{{ $card['name'] }}</h3>
                        <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed mb-4">{{ $card['description'] }}</p>

                        <!-- Blueprint Metrics -->
                        <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-2 mb-4">
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-600">Assigned in Exam:</span>
                                <span class="font-bold text-slate-900">{{ $card['assigned_items'] }} items</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-600">Available in Bank:</span>
                                <span class="font-semibold {{ $card['available_count'] >= $card['assigned_items'] ? 'text-emerald-700' : 'text-amber-700' }}">
                                    {{ $card['available_count'] }} items
                                </span>
                            </div>
                            <div class="flex justify-between text-xs pt-1 border-t border-slate-200/60">
                                <span class="text-slate-600">Your Average:</span>
                                <span class="font-bold text-[#174A8B]">{{ number_format($card['performance'], 1) }}%</span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('exams.category', ['category_id' => $card['id']]) }}"
                       class="w-full inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-bold text-white transition shadow-xs"
                       style="background-color: {{ $card['color'] }}">
                        <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                        <span>Practice {{ $card['name'] }}</span>
                    </a>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Two Column Grid: Performance Analytics & Streak Goal -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Performance Overview (2 cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-slate-200 shadow-xs space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Performance Summary</h3>
                    <p class="text-xs text-slate-500">Historical performance by subject domain</p>
                </div>
                <a href="{{ route('analytics') }}" class="text-xs font-semibold text-[#174A8B] hover:underline flex items-center gap-1">
                    <span>Full Analytics</span>
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <!-- Weakest & Strongest Pills -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl bg-rose-50/70 border border-rose-100 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-rose-100 text-[#C92535] flex items-center justify-center">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold text-rose-800 uppercase tracking-wider block">Priority Focus Area</span>
                        <span class="text-sm font-bold text-slate-900">{{ $analytics['weakest_category'] ?? 'None identified yet' }}</span>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-blue-50/70 border border-blue-100 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 text-[#174A8B] flex items-center justify-center">
                        <i data-lucide="award" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold text-blue-900 uppercase tracking-wider block">Strongest Category</span>
                        <span class="text-sm font-bold text-slate-900">{{ $analytics['strongest_category'] ?? 'None identified yet' }}</span>
                    </div>
                </div>
            </div>

            <!-- Category Accuracy Progress Bars -->
            <div class="space-y-4 pt-2">
                @foreach ($analytics['category_stats'] as $catStat)
                    <div>
                        <div class="flex justify-between items-center text-xs mb-1.5">
                            <span class="font-semibold text-slate-800">{{ $catStat['name'] }}</span>
                            <span class="font-bold text-slate-900">{{ number_format($catStat['percentage'], 1) }}% ({{ $catStat['correct_count'] }}/{{ $catStat['attempted_count'] }})</span>
                        </div>
                        <div class="w-full h-2.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500"
                                 style="width: {{ min(100, max(0, $catStat['percentage'])) }}%; background-color: {{ $catStat['color'] }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Daily Streak & Goal Progress (1 col) -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex flex-col justify-between space-y-6">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-base font-bold text-slate-900">Today's Study Goal</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $streaks['daily_target_met'] ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $streaks['daily_target_met'] ? 'Goal Completed!' : 'In Progress' }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed mb-6">
                    Solve at least {{ $streaks['daily_target'] }} questions today to maintain your consecutive preparation streak.
                </p>

                <!-- Goal Progress Bar -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-600 font-medium">Questions Answered Today:</span>
                        <span class="text-sm font-bold text-slate-900">{{ $streaks['today_questions_count'] }} / {{ $streaks['daily_target'] }}</span>
                    </div>

                    @php
                        $goalPct = $streaks['daily_target'] > 0
                            ? min(100, round(($streaks['today_questions_count'] / $streaks['daily_target']) * 100))
                            : 0;
                    @endphp
                    <div class="w-full h-3 rounded-full bg-slate-200 overflow-hidden">
                        <div class="h-full rounded-full bg-[#C92535] transition-all duration-500" style="width: {{ $goalPct }}%"></div>
                    </div>
                    <div class="text-right text-[11px] font-semibold text-slate-500">
                        {{ $goalPct }}% of daily target reached
                    </div>
                </div>

                <!-- Streak Stats Card -->
                <div class="mt-4 grid grid-cols-2 gap-3 text-center">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-[11px] text-slate-500 uppercase tracking-wider block">Current Streak</span>
                        <span class="text-xl font-bold text-[#C92535]">{{ $streaks['current_streak'] }} Days</span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-[11px] text-slate-500 uppercase tracking-wider block">Longest Streak</span>
                        <span class="text-xl font-bold text-slate-800">{{ $streaks['longest_streak'] }} Days</span>
                    </div>
                </div>
            </div>

            <a href="{{ route('streak-calendar') }}"
               class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-[#174A8B] bg-blue-50 hover:bg-blue-100 transition border border-blue-200">
                <i data-lucide="calendar-days" class="w-4 h-4"></i>
                <span>Open Streak Calendar</span>
            </a>
        </div>
    </div>

    <!-- Recent Examination Activity -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Recent Examination Activity</h3>
                <p class="text-xs text-slate-500">Review your recent full mock exams and practice tests</p>
            </div>
            <a href="{{ route('exams.history') }}" class="text-xs font-semibold text-[#174A8B] hover:underline flex items-center gap-1">
                <span>View All History</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>

        @if ($recentAttempts->isEmpty())
            <div class="p-12 text-center">
                <div class="w-16 h-16 mx-auto rounded-full bg-blue-50 text-[#174A8B] flex items-center justify-center mb-3">
                    <i data-lucide="graduation-cap" class="w-8 h-8"></i>
                </div>
                <h4 class="text-sm font-bold text-slate-800 mb-1">No Practice Examinations Yet</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mb-4">
                    Ready to test your readiness? Start a full 150-item simulation or a quick category practice session.
                </p>
                <div class="flex justify-center gap-3">
                    <a href="{{ route('exams.full') }}" class="px-4 py-2 rounded-lg text-xs font-bold bg-[#C92535] text-white hover:bg-[#A81B29] transition">
                        Start Full Exam
                    </a>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="px-6 py-3.5">Examination</th>
                            <th class="px-6 py-3.5">Mode</th>
                            <th class="px-6 py-3.5">Date Completed</th>
                            <th class="px-6 py-3.5">Score</th>
                            <th class="px-6 py-3.5">Result</th>
                            <th class="px-6 py-3.5">Duration</th>
                            <th class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @foreach ($recentAttempts as $attempt)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    {{ $attempt->title }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $attempt->mode === \App\Enums\ExamMode::Full ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                        {{ $attempt->mode->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    {{ $attempt->submitted_at ? $attempt->submitted_at->format('M d, Y h:i A') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-900 text-sm">{{ number_format((float) $attempt->score_percentage, 1) }}%</span>
                                    <span class="text-slate-400 block text-[11px]">({{ $attempt->correct_count }}/{{ $attempt->total_questions }})</span>
                                </td>
                                <td class="px-6 py-4">
                                    @if ((float) $attempt->score_percentage >= 80.00)
                                        <span class="inline-flex items-center gap-1 text-emerald-700 font-bold">
                                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                                            <span>PASSED</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-rose-700 font-bold">
                                            <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                                            <span>FAILED</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    {{ $attempt->duration_minutes }} min
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('results.show', ['attempt' => $attempt->id]) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-[#174A8B] hover:bg-blue-50 border border-blue-200 transition">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        <span>Review</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
