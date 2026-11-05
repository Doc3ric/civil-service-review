<div class="max-w-3xl mx-auto space-y-8">
    <div class="text-center space-y-2">
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Focused Category Practice</h2>
        <p class="text-xs text-slate-500 max-w-md mx-auto">
            Strengthen your weak subject areas with targeted practice questions and instant feedback.
        </p>
    </div>

    <!-- Category Selector Grid -->
    <div class="space-y-3">
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Select Examination Category</label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ($categories as $cat)
                @php $isSelected = ($selectedCategoryId === $cat->id); @endphp
                <button type="button"
                        wire:click="$set('selectedCategoryId', {{ $cat->id }})"
                        class="p-5 rounded-2xl border text-left transition relative flex flex-col justify-between {{ $isSelected ? 'bg-blue-50/70 border-[#174A8B] ring-2 ring-[#174A8B]/20 shadow-xs' : 'bg-white border-slate-200 hover:border-slate-300' }}">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white text-sm"
                             style="background-color: {{ $cat->color }}">
                            {{ substr($cat->code instanceof \BackedEnum ? $cat->code->value : $cat->code, 0, 3) }}
                        </div>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $cat->published_count > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                            {{ $cat->published_count }} Available
                        </span>
                    </div>

                    <div>
                        <h4 class="text-sm font-bold text-slate-900 mb-1">{{ $cat->name }}</h4>
                        <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">{{ $cat->description }}</p>
                    </div>

                    @if ($isSelected)
                        <div class="absolute top-3 right-3 text-[#174A8B]">
                            <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                        </div>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    <!-- Question Count Selector -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs space-y-4">
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Select Number of Practice Questions</label>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            @foreach ([10, 20, 30, 50, 9999] as $countOpt)
                @php
                    $isCountSelected = ($selectedCount === $countOpt);
                    $label = ($countOpt === 9999) ? 'All Items' : "{$countOpt} Items";
                @endphp
                <button type="button"
                        wire:click="$set('selectedCount', {{ $countOpt }})"
                        class="py-3 px-4 rounded-xl text-xs font-bold text-center border transition {{ $isCountSelected ? 'bg-[#174A8B] text-white border-[#174A8B] shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <p class="text-xs text-slate-400">
            Current Category: <strong class="text-slate-700">{{ $currentCat?->name }}</strong> ({{ $currentCat?->published_count ?? 0 }} published questions ready).
        </p>
    </div>

    <!-- Start Button -->
    <div class="text-center pt-2">
        <button wire:click="startPractice"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-8 py-3.5 rounded-2xl font-bold text-sm text-white bg-[#C92535] hover:bg-[#A81B29] transition shadow-md disabled:opacity-50">
            <span wire:loading.remove wire:target="startPractice">
                <i data-lucide="sliders-horizontal" class="w-5 h-5 inline mr-1"></i>
                Start {{ $currentCat?->name }} Practice
            </span>
            <span wire:loading wire:target="startPractice">Preparing Session...</span>
        </button>
    </div>
</div>
