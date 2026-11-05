<div class="space-y-8">
    <!-- Results Banner -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-6 border-b border-slate-100">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider mb-2 {{ $scoreData['is_passed'] ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                    <i data-lucide="{{ $scoreData['is_passed'] ? 'check-circle-2' : 'alert-circle' }}" class="w-4 h-4"></i>
                    <span>{{ $scoreData['is_passed'] ? 'CSC Standard PASSED' : 'Did Not Meet 80% Target' }}</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    {{ $attempt->title }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Completed on {{ $attempt->submitted_at ? $attempt->submitted_at->format('F j, Y \\a\\t h:i A') : 'N/A' }} • Duration: {{ $attempt->duration_minutes }} minutes
                </p>
            </div>

            <!-- Score Pill -->
            <div class="text-center md:text-right bg-slate-50 p-4 sm:px-6 rounded-2xl border border-slate-200">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Percentage Score</span>
                <span class="text-3xl sm:text-4xl font-extrabold {{ $scoreData['is_passed'] ? 'text-emerald-600' : 'text-[#C92535]' }}">
                    {{ number_format($scoreData['percentage'], 2) }}%
                </span>
                <span class="text-xs text-slate-400 block mt-0.5">Passing Standard: 80.00%</span>
            </div>
        </div>

        <!-- 4 Metrics Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-6">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 text-center">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Total Questions</span>
                <span class="text-2xl font-extrabold text-slate-800 mt-1 block">{{ $scoreData['total'] }}</span>
            </div>
            <div class="p-4 rounded-xl bg-emerald-50/80 border border-emerald-100 text-center">
                <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider block">Correct Answers</span>
                <span class="text-2xl font-extrabold text-emerald-700 mt-1 block">{{ $scoreData['correct'] }}</span>
            </div>
            <div class="p-4 rounded-xl bg-rose-50/80 border border-rose-100 text-center">
                <span class="text-[11px] font-bold text-rose-700 uppercase tracking-wider block">Incorrect Answers</span>
                <span class="text-2xl font-extrabold text-rose-700 mt-1 block">{{ $scoreData['incorrect'] }}</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-100 border border-slate-200 text-center">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Unanswered</span>
                <span class="text-2xl font-extrabold text-slate-700 mt-1 block">{{ $scoreData['unanswered'] }}</span>
            </div>
        </div>
    </div>

    <!-- Category Performance Breakdown -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs space-y-4">
        <h3 class="text-base font-bold text-slate-900">Subject Category Breakdown</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($scoreData['category_breakdown'] as $catCode => $cat)
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <div class="flex justify-between items-center text-xs">
                        <span class="font-bold text-slate-900">{{ $cat['name'] }}</span>
                        <span class="font-extrabold {{ $cat['percentage'] >= 80 ? 'text-emerald-700' : 'text-[#C92535]' }}">
                            {{ number_format($cat['percentage'], 1) }}%
                        </span>
                    </div>

                    <div class="w-full h-2 rounded-full bg-slate-200 overflow-hidden">
                        <div class="h-full rounded-full {{ $cat['percentage'] >= 80 ? 'bg-emerald-600' : 'bg-[#C92535]' }}"
                             style="width: {{ $cat['percentage'] }}%"></div>
                    </div>

                    <div class="flex justify-between text-[11px] text-slate-500 pt-1">
                        <span>{{ $cat['correct'] }} of {{ $cat['total'] }} Correct</span>
                        @if ($cat['unanswered'] > 0)
                            <span class="text-amber-700">{{ $cat['unanswered'] }} Unanswered</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Detailed Question & Answer Review Section -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden space-y-6">
        <!-- Review Controls & Filter Bar -->
        <div class="p-6 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/60">
            <div>
                <h3 class="text-base font-bold text-slate-900">Comprehensive Item Review</h3>
                <p class="text-xs text-slate-500">Examine correct answers and detailed explanations</p>
            </div>

            <!-- Filter Tabs -->
            <div class="flex flex-wrap items-center gap-1.5 text-xs font-semibold">
                <button wire:click="$set('reviewFilter', 'all')"
                        class="px-3 py-1.5 rounded-lg transition {{ $reviewFilter === 'all' ? 'bg-[#174A8B] text-white shadow-xs' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' }}">
                    All Items ({{ $totalItemsCount }})
                </button>
                <button wire:click="$set('reviewFilter', 'incorrect')"
                        class="px-3 py-1.5 rounded-lg transition {{ $reviewFilter === 'incorrect' ? 'bg-[#C92535] text-white shadow-xs' : 'bg-rose-50 text-rose-800 border border-rose-200 hover:bg-rose-100' }}">
                    Incorrect ({{ $scoreData['incorrect'] }})
                </button>
                <button wire:click="$set('reviewFilter', 'unanswered')"
                        class="px-3 py-1.5 rounded-lg transition {{ $reviewFilter === 'unanswered' ? 'bg-slate-700 text-white shadow-xs' : 'bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200' }}">
                    Unanswered ({{ $scoreData['unanswered'] }})
                </button>
                <button wire:click="$set('reviewFilter', 'correct')"
                        class="px-3 py-1.5 rounded-lg transition {{ $reviewFilter === 'correct' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100' }}">
                    Correct ({{ $scoreData['correct'] }})
                </button>
            </div>
        </div>

        <!-- Questions Review List -->
        <div class="p-6 space-y-8 divide-y divide-slate-100">
            @forelse ($reviewItems as $item)
                <div class="pt-6 first:pt-0 space-y-4">
                    <!-- Item Header -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg font-bold text-xs bg-slate-100 text-slate-800 font-mono">
                                #{{ $item['question_number'] }}
                            </span>
                            <span class="text-xs font-bold text-[#174A8B] uppercase tracking-wider">
                                {{ $item['category_name'] }}
                            </span>
                        </div>

                        <!-- Status Badge -->
                        @if ($item['status_badge'] === 'correct')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                                <span>Correct</span>
                            </span>
                        @elseif ($item['status_badge'] === 'unanswered')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-300">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                <span>Unanswered</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                                <span>Incorrect</span>
                            </span>
                        @endif
                    </div>

                    <!-- Question Prompt -->
                    <div class="text-sm sm:text-base font-semibold text-slate-900 leading-relaxed">
                        {{ $item['question_text'] }}
                    </div>

                    <!-- Choices Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        @foreach ($item['choices'] as $choice)
                            @php
                                $isCorrect = ($choice['label'] === $item['correct_choice']);
                                $isUserPicked = ($choice['label'] === $item['selected_choice']);
                            @endphp
                            <div class="p-3 rounded-xl border text-xs flex items-start gap-3 {{ $isCorrect ? 'bg-emerald-50/80 border-emerald-300 text-emerald-950 font-semibold' : ($isUserPicked ? 'bg-rose-50/80 border-rose-300 text-rose-950 font-semibold' : 'bg-slate-50/50 border-slate-200 text-slate-700') }}">
                                <span class="w-6 h-6 rounded-full shrink-0 flex items-center justify-center font-bold text-xs {{ $isCorrect ? 'bg-emerald-600 text-white' : ($isUserPicked ? 'bg-[#C92535] text-white' : 'bg-slate-200 text-slate-700') }}">
                                    {{ $choice['label'] }}
                                </span>
                                <div class="flex-1 pt-0.5">
                                    {{ $choice['text'] }}
                                </div>
                                @if ($isCorrect)
                                    <span class="text-[10px] font-bold text-emerald-700 uppercase shrink-0 pt-0.5">Correct Answer</span>
                                @elseif ($isUserPicked)
                                    <span class="text-[10px] font-bold text-rose-700 uppercase shrink-0 pt-0.5">Your Choice</span>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <!-- Explanation Card -->
                    @if ($item['explanation'])
                        <div class="p-4 rounded-xl bg-blue-50/70 border border-blue-100 text-xs text-blue-900 leading-relaxed flex items-start gap-2.5">
                            <i data-lucide="help-circle" class="w-4 h-4 text-[#174A8B] shrink-0 mt-0.5"></i>
                            <div>
                                <span class="font-bold text-[#174A8B] block mb-0.5">Explanation & Rationale:</span>
                                {{ $item['explanation'] }}
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-8 text-center text-slate-500 text-xs">
                    No questions match the selected filter.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Bottom Actions -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <a href="{{ route('exams.history') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition">
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>Back to History</span>
        </a>

        <div class="flex items-center gap-3">
            <a href="{{ route('exams.category') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold text-[#174A8B] bg-blue-50 hover:bg-blue-100 transition border border-blue-200">
                <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                <span>Practice Weak Category</span>
            </a>
            <a href="{{ route('exams.full') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-[#C92535] hover:bg-[#A81B29] transition shadow-xs">
                <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                <span>Take Another 150 Exam</span>
            </a>
        </div>
    </div>
</div>
