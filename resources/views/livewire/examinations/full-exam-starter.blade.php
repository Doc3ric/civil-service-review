<div class="max-w-4xl mx-auto space-y-8">
    <!-- Exam Title Card -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-xs text-center space-y-3">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-50 text-xs font-bold tracking-wide text-[#C92535] border border-rose-100">
            <span class="w-2 h-2 rounded-full bg-[#C92535]"></span>
            <span>Official Examination Simulation</span>
        </div>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
            {{ $blueprint->name }}
        </h2>
        <p class="text-sm text-slate-500 max-w-2xl mx-auto leading-relaxed">
            {{ $blueprint->description }}
        </p>

        <div class="flex flex-wrap items-center justify-center gap-6 pt-4 text-xs font-semibold text-slate-600">
            <div class="flex items-center gap-2">
                <i data-lucide="calculator" class="w-4 h-4 text-[#174A8B]"></i>
                <span>{{ $blueprint->total_questions }} Questions</span>
            </div>
            <div class="flex items-center gap-2">
                <i data-lucide="clock" class="w-4 h-4 text-[#174A8B]"></i>
                <span>{{ $blueprint->duration_minutes }} Minutes (2h 50m)</span>
            </div>
            <div class="flex items-center gap-2">
                <i data-lucide="award" class="w-4 h-4 text-[#C92535]"></i>
                <span>{{ number_format($blueprint->passing_percentage, 1) }}% Passing Standard</span>
            </div>
        </div>
    </div>

    <!-- Category Allocation Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-200 bg-slate-50/60 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Configured Examination Blueprint</h3>
                <p class="text-xs text-slate-500">Subject distributions randomized from the published Question Bank</p>
            </div>
            <span class="text-xs font-bold text-[#174A8B] bg-blue-50 px-2.5 py-1 rounded-full border border-blue-200">
                Total: 150 Items
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-semibold tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Category</th>
                        <th class="px-6 py-3.5 text-center">Assigned Items</th>
                        <th class="px-6 py-3.5 text-center">Exam Percentage</th>
                        <th class="px-6 py-3.5 text-center">Bank Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @foreach ($blueprint->blueprintCategories as $bc)
                        @php
                            $available = \App\Models\Question::published()->where('category_id', $bc->category_id)->count();
                            $hasEnough = $available >= $bc->question_count;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4 font-bold text-slate-900 flex items-center gap-2.5">
                                <span class="w-3 h-3 rounded-full" style="background-color: {{ $bc->category->color }}"></span>
                                <span>{{ $bc->category->name }}</span>
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-slate-800 text-sm">
                                {{ $bc->question_count }}
                            </td>
                            <td class="px-6 py-4 text-center font-semibold text-slate-600">
                                {{ number_format($bc->percentage, 1) }}%
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if ($hasEnough)
                                    <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold text-xs">
                                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                                        <span>Ready ({{ $available }} available)</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-rose-700 font-semibold text-xs">
                                        <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                        <span>Deficit: only {{ $available }} published</span>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Examination Instructions & Policies -->
    <div class="bg-blue-50/70 rounded-2xl p-6 border border-blue-100 text-xs text-blue-950 space-y-3">
        <h4 class="font-bold text-sm text-[#174A8B] flex items-center gap-2">
            <i data-lucide="award" class="w-4 h-4"></i>
            <span>Examination Simulation Instructions</span>
        </h4>
        <ul class="list-disc list-inside space-y-1.5 text-blue-900/90 leading-relaxed">
            <li><strong>Timer Enforcement:</strong> The 170-minute countdown is tracked and enforced on the server. If the timer reaches zero, the examination will be submitted automatically.</li>
            <li><strong>Randomization:</strong> Both questions and multiple-choice answer placements (A, B, C, D) are randomized to reinforce conceptual understanding.</li>
            <li><strong>Auto-Save:</strong> Selected answers are persisted continuously to the server. You may refresh or reopen your session without loss of progress.</li>
            <li><strong>Answer Explanations:</strong> Correct answers and comprehensive rationales will be revealed upon final submission.</li>
        </ul>
    </div>

    <!-- Start Action -->
    <div class="text-center pt-2">
        @if ($availability['sufficient'])
            <button wire:click="startExam"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2.5 px-8 py-4 rounded-2xl font-extrabold text-base text-white bg-[#C92535] hover:bg-[#A81B29] transition shadow-lg ring-4 ring-rose-500/20 disabled:opacity-50">
                <span wire:loading.remove wire:target="startExam">
                    <i data-lucide="graduation-cap" class="w-6 h-6 inline mr-1"></i>
                    Begin 150-Item Examination
                </span>
                <span wire:loading wire:target="startExam">Generating Examination Questions...</span>
            </button>
        @else
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold max-w-lg mx-auto">
                Cannot start examination. Please import or publish enough questions to fulfill the blueprint requirements.
            </div>
        @endif
    </div>
</div>
