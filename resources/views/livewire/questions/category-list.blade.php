<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Examination Subject Categories</h2>
            <p class="text-xs text-slate-500">Official syllabus divisions for the Civil Service Professional Level Examination</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('questions.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold text-[#174A8B] bg-blue-50 hover:bg-blue-100 transition border border-blue-200">
                <i data-lucide="database" class="w-4 h-4"></i>
                <span>Open Question Bank</span>
            </a>
        </div>
    </div>

    <!-- Category Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach ($categories as $cat)
            @php
                $bpCategory = $blueprint?->blueprintCategories->firstWhere('category_id', $cat->id);
                $assignedCount = $bpCategory?->question_count ?? 0;
                $assignedPct = $bpCategory?->percentage ?? 0;
            @endphp
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center font-bold text-white text-base shadow-xs"
                             style="background-color: {{ $cat->color }}">
                            {{ substr($cat->code instanceof \BackedEnum ? $cat->code->value : $cat->code, 0, 3) }}
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                                  style="background-color: {{ $cat->color }}15; color: {{ $cat->color }}">
                                {{ $assignedPct }}% of 150 Exam
                            </span>
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-slate-900 mb-1">{{ $cat->name }}</h3>
                    <p class="text-xs text-slate-500 leading-relaxed mb-6">{{ $cat->description }}</p>

                    <!-- Stats Breakdown -->
                    <div class="grid grid-cols-3 gap-3 p-3.5 rounded-xl bg-slate-50 border border-slate-100 text-center mb-6">
                        <div>
                            <span class="text-[11px] font-semibold text-slate-500 block">Published</span>
                            <span class="text-base font-bold text-emerald-700">{{ $cat->published_count }}</span>
                        </div>
                        <div>
                            <span class="text-[11px] font-semibold text-slate-500 block">Assigned</span>
                            <span class="text-base font-bold text-slate-800">{{ $assignedCount }}</span>
                        </div>
                        <div>
                            <span class="text-[11px] font-semibold text-slate-500 block">Archived</span>
                            <span class="text-base font-bold text-slate-400">{{ $cat->archived_count }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <a href="{{ route('questions.index', ['categoryId' => $cat->id]) }}"
                       class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition">
                        <i data-lucide="database" class="w-4 h-4"></i>
                        <span>Filter in Bank</span>
                    </a>
                    <a href="{{ route('exams.category', ['category_id' => $cat->id]) }}"
                       class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold text-white transition shadow-xs"
                       style="background-color: {{ $cat->color }}">
                        <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                        <span>Practice Now</span>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
