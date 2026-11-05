<div class="space-y-6"
     x-data="{
         timeLeft: {{ $remainingSeconds }},
         timerInterval: null,
         formatTime(seconds) {
             const h = Math.floor(seconds / 3600);
             const m = Math.floor((seconds % 3600) / 60);
             const s = seconds % 60;
             if (h > 0) {
                 return `${h}:${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
             }
             return `${m}:${s < 10 ? '0' : ''}${s}`;
         },
         startTimer() {
             this.timerInterval = setInterval(() => {
                 if (this.timeLeft > 0) {
                     this.timeLeft--;
                 } else {
                     clearInterval(this.timerInterval);
                     $wire.handleTimeExpired();
                 }
             }, 1000);
         }
     }"
     x-init="startTimer()">

    <!-- Sticky Exam Controls Header -->
    <div class="bg-white rounded-2xl p-4 sm:px-6 border border-slate-200 shadow-xs sticky top-16 z-20 flex flex-wrap items-center justify-between gap-4">
        <!-- Question Counter & Category -->
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-blue-50 text-[#174A8B] font-bold text-xs border border-blue-200">
                Question {{ $currentQuestionNumber }} of {{ $totalQuestions }}
            </span>
            <span class="hidden sm:inline-block text-xs font-semibold text-slate-500">
                {{ $currentQuestion->category_name_snapshot }}
            </span>
        </div>

        <!-- Timer & Submit Button -->
        <div class="flex items-center gap-4">
            <!-- Countdown Clock -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl border text-xs font-mono font-bold"
                 :class="timeLeft <= 300 ? 'bg-rose-50 text-rose-700 border-rose-300 animate-pulse' : 'bg-slate-50 text-slate-800 border-slate-200'">
                <i data-lucide="clock" class="w-4 h-4"></i>
                <span x-text="formatTime(timeLeft)"></span>
            </div>

            <button wire:click="openSubmitModal"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#C92535] hover:bg-[#A81B29] transition shadow-xs">
                <i data-lucide="flag" class="w-3.5 h-3.5"></i>
                <span>Submit Exam</span>
            </button>
        </div>
    </div>

    <!-- Main Two-Column Layout (Question Area + Question Palette) -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Main Question Area (3 cols) -->
        <div class="lg:col-span-3 space-y-6">
            <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-xs space-y-6">
                <!-- Question Header -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <span class="text-xs font-bold text-[#174A8B] uppercase tracking-wider">
                        Subject: {{ $currentQuestion->category_name_snapshot }}
                    </span>
                    <span class="text-xs text-slate-400">
                        Item #{{ $currentQuestionNumber }}
                    </span>
                </div>

                <!-- Question Prompt -->
                <div class="text-base sm:text-lg font-semibold text-slate-900 leading-relaxed">
                    {{ $currentQuestion->question_text_snapshot }}
                </div>

                <!-- 4 Choices List -->
                <div class="space-y-3 pt-2">
                    @foreach ($currentQuestion->shuffled_choices_json as $choice)
                        @php
                            $isSelected = ($currentAnswer === $choice['label']);
                        @endphp
                        <button type="button"
                                wire:click="selectAnswer('{{ $choice['label'] }}')"
                                class="w-full text-left p-4 rounded-xl border transition flex items-start gap-4 group {{ $isSelected ? 'bg-blue-50/80 border-[#174A8B] ring-2 ring-[#174A8B]/20 text-[#0F3260]' : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-800' }}">
                            <span class="w-7 h-7 rounded-full shrink-0 flex items-center justify-center font-bold text-xs transition {{ $isSelected ? 'bg-[#174A8B] text-white' : 'bg-slate-100 text-slate-600 group-hover:bg-slate-200' }}">
                                {{ $choice['label'] }}
                            </span>
                            <div class="pt-0.5 text-sm leading-relaxed flex-1">
                                {{ $choice['text'] }}
                            </div>
                            @if ($isSelected)
                                <div class="text-[#174A8B] pt-0.5">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                </div>
                            @endif
                        </button>
                    @endforeach
                </div>

                <!-- Navigation Controls -->
                <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
                    <button type="button"
                            wire:click="previousQuestion"
                            @if ($currentQuestionNumber <= 1) disabled @endif
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition disabled:opacity-30 disabled:cursor-not-allowed">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                        <span>Previous Question</span>
                    </button>

                    <div class="text-xs text-slate-500 font-medium">
                        {{ $answeredCount }} of {{ $totalQuestions }} Answered
                    </div>

                    <button type="button"
                            wire:click="nextQuestion"
                            @if ($currentQuestionNumber >= $totalQuestions) disabled @endif
                            class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-[#174A8B] hover:bg-blue-900 transition disabled:opacity-30 disabled:cursor-not-allowed shadow-xs">
                        <span>Next Question</span>
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Question Navigation Palette Sidebar (1 col) -->
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Question Palette</h4>
                    <span class="text-xs font-bold text-[#174A8B]">{{ $answeredCount }}/{{ $totalQuestions }}</span>
                </div>

                <!-- Palette Progress Bar -->
                <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-[#174A8B] transition-all duration-300"
                         style="width: {{ $totalQuestions > 0 ? round(($answeredCount / $totalQuestions) * 100) : 0 }}%"></div>
                </div>

                <!-- Legend -->
                <div class="grid grid-cols-3 gap-1 text-[10px] text-slate-500 text-center pb-2 border-b border-slate-100">
                    <div class="flex items-center justify-center gap-1">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#174A8B]"></span>
                        <span>Answered</span>
                    </div>
                    <div class="flex items-center justify-center gap-1">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-200 border border-slate-300"></span>
                        <span>Unanswered</span>
                    </div>
                    <div class="flex items-center justify-center gap-1">
                        <span class="w-2.5 h-2.5 rounded-full bg-white ring-2 ring-[#C92535]"></span>
                        <span>Current</span>
                    </div>
                </div>

                <!-- Grid of Question Numbers -->
                <div class="grid grid-cols-5 gap-1.5 max-h-[380px] overflow-y-auto exam-scroll p-1">
                    @for ($i = 1; $i <= $totalQuestions; $i++)
                        @php
                            $isAns = !empty($userAnswers[$i]);
                            $isCur = ($currentQuestionNumber === $i);
                        @endphp
                        <button type="button"
                                wire:click="goToQuestion({{ $i }})"
                                class="h-8 rounded-lg text-xs font-bold transition flex items-center justify-center {{ $isCur ? 'ring-2 ring-[#C92535] font-extrabold text-[#C92535] bg-rose-50 shadow-xs' : ($isAns ? 'bg-[#174A8B] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200') }}">
                            {{ $i }}
                        </button>
                    @endfor
                </div>

                <!-- Fast Submit Action in Sidebar -->
                <div class="pt-2">
                    <button wire:click="openSubmitModal"
                            class="w-full py-2.5 rounded-xl text-xs font-bold text-white bg-[#C92535] hover:bg-[#A81B29] transition shadow-xs">
                        Finish & Submit Exam
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Confirmation Modal -->
    @if ($showSubmitModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/60 transition-opacity" wire:click="closeSubmitModal"></div>

                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                    <div class="p-6 text-center space-y-4">
                        <div class="w-14 h-14 mx-auto rounded-full bg-rose-50 text-[#C92535] flex items-center justify-center">
                            <i data-lucide="flag" class="w-7 h-7"></i>
                        </div>

                        <h3 class="text-lg font-bold text-slate-900">
                            Confirm Examination Submission
                        </h3>

                        <p class="text-xs text-slate-500 leading-relaxed">
                            Are you ready to submit your answers? Once submitted, your score will be calculated and answers cannot be altered.
                        </p>

                        <!-- Summary Counters -->
                        <div class="grid grid-cols-3 gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                            <div>
                                <span class="text-slate-500 block text-[11px]">Total Items</span>
                                <span class="font-bold text-slate-900 text-sm">{{ $totalQuestions }}</span>
                            </div>
                            <div>
                                <span class="text-emerald-700 block text-[11px] font-semibold">Answered</span>
                                <span class="font-bold text-emerald-800 text-sm">{{ $answeredCount }}</span>
                            </div>
                            <div>
                                <span class="text-rose-700 block text-[11px] font-semibold">Unanswered</span>
                                <span class="font-bold text-rose-800 text-sm">{{ $unansweredCount }}</span>
                            </div>
                        </div>

                        @if ($unansweredCount > 0)
                            <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs text-left">
                                <strong>Notice:</strong> You have <strong>{{ $unansweredCount }} unanswered questions</strong>. Any unanswered questions will be marked incorrect.
                            </div>
                        @endif
                    </div>

                    <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-end gap-3">
                        <button type="button" wire:click="closeSubmitModal"
                                class="px-4 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-100">
                            Return to Exam
                        </button>
                        <button type="button" wire:click="submitExam"
                                wire:loading.attr="disabled"
                                class="px-5 py-2 rounded-lg text-xs font-bold text-white bg-[#C92535] hover:bg-[#A81B29] transition shadow-xs disabled:opacity-50">
                            <span wire:loading.remove wire:target="submitExam">Submit & View Results</span>
                            <span wire:loading wire:target="submitExam">Calculating Scores...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
