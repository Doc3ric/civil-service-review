<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Examination Records & Scoring History</h2>
            <p class="text-xs text-slate-500">Historical performance archive for full mock examinations and category practice sessions</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('exams.full') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#C92535] hover:bg-[#A81B29] transition shadow-xs">
                <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                <span>Take 150 Exam</span>
            </a>
            <a href="{{ route('exams.category') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition">
                <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                <span>Category Practice</span>
            </a>
        </div>
    </div>

    <!-- Mode Filter Tabs -->
    <div class="flex items-center gap-2">
        <button wire:click="$set('modeFilter', '')"
                class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ $modeFilter === '' ? 'bg-[#174A8B] text-white shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            All Examinations
        </button>
        <button wire:click="$set('modeFilter', 'full')"
                class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ $modeFilter === 'full' ? 'bg-[#174A8B] text-white shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            Full Practice (150 Items)
        </button>
        <button wire:click="$set('modeFilter', 'category')"
                class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ $modeFilter === 'category' ? 'bg-[#174A8B] text-white shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            Category Practice
        </button>
    </div>

    <!-- Attempts Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        @if ($attempts->isEmpty())
            <div class="p-12 text-center">
                <div class="w-16 h-16 mx-auto rounded-full bg-blue-50 text-[#174A8B] flex items-center justify-center mb-3">
                    <i data-lucide="history" class="w-8 h-8"></i>
                </div>
                <h4 class="text-sm font-bold text-slate-800 mb-1">No Examination Records</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mb-4">
                    You have not completed any practice examinations yet. Begin preparing today!
                </p>
                <a href="{{ route('exams.full') }}" class="px-4 py-2 rounded-lg text-xs font-bold bg-[#C92535] text-white hover:bg-[#A81B29] transition">
                    Start Full Practice Exam
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="px-6 py-3.5 w-16">#ID</th>
                            <th class="px-6 py-3.5">Examination Title</th>
                            <th class="px-6 py-3.5">Mode</th>
                            <th class="px-6 py-3.5">Date Completed</th>
                            <th class="px-6 py-3.5 text-center">Score</th>
                            <th class="px-6 py-3.5 text-center">Evaluation</th>
                            <th class="px-6 py-3.5 text-center">Duration</th>
                            <th class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @foreach ($attempts as $a)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-6 py-4 font-mono font-bold text-slate-400">
                                    #{{ $a->id }}
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-900">
                                    {{ $a->title }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $a->mode === \App\Enums\ExamMode::Full ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                        {{ $a->mode->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-500 whitespace-nowrap">
                                    {{ $a->submitted_at ? $a->submitted_at->format('M d, Y h:i A') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-bold text-slate-900 text-sm">{{ number_format((float) $a->score_percentage, 1) }}%</span>
                                    <span class="text-slate-400 block text-[11px]">({{ $a->correct_count }} / {{ $a->total_questions }})</span>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    @if ((float) $a->score_percentage >= 80.00)
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
                                <td class="px-6 py-4 text-center text-slate-500">
                                    {{ $a->duration_minutes }} min
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <a href="{{ route('results.show', ['attempt' => $a->id]) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-[#174A8B] hover:bg-blue-50 border border-blue-200 transition">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        <span>View Results</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
                {{ $attempts->links() }}
            </div>
        @endif
    </div>
</div>
