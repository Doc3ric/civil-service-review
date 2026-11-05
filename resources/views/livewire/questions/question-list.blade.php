<div class="space-y-6">
    <!-- Header / Actions Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Question Bank Repository</h2>
            <p class="text-xs text-slate-500">Centralized database of civil service practice questions</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('questions.import') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-[#174A8B] bg-blue-50 hover:bg-blue-100 transition border border-blue-200">
                <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                <span>Import via Excel</span>
            </a>
            <button wire:click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#C92535] hover:bg-[#A81B29] transition shadow-xs">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>New Question</span>
            </button>
            <button wire:click="confirmDeleteAll"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-slate-700 hover:bg-slate-900 transition shadow-xs">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>Delete All</span>
            </button>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <!-- Search input -->
        <div class="lg:col-span-2 relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <i data-lucide="search" class="w-4 h-4"></i>
            </div>
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search questions, choices, sources..."
                   class="block w-full pl-9 pr-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent">
        </div>

        <!-- Category filter -->
        <div>
            <select wire:model.live="categoryId"
                    class="block w-full py-2 px-3 border border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent">
                <option value="">All Categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Difficulty filter -->
        <div>
            <select wire:model.live="difficulty"
                    class="block w-full py-2 px-3 border border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent">
                <option value="">All Difficulties</option>
                <option value="easy">Easy</option>
                <option value="medium">Medium</option>
                <option value="hard">Hard</option>
            </select>
        </div>

        <!-- Status filter -->
        <div>
            <select wire:model.live="status"
                    class="block w-full py-2 px-3 border border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent">
                <option value="">All Statuses</option>
                <option value="published">Published</option>
                <option value="draft">Draft</option>
                <option value="archived">Archived</option>
            </select>
        </div>
    </div>

    <!-- Questions Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        @if ($questions->isEmpty())
            <div class="p-12 text-center">
                <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
                    <i data-lucide="database" class="w-8 h-8"></i>
                </div>
                <h4 class="text-sm font-bold text-slate-800 mb-1">No Questions Found</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mb-4">
                    No questions match your current search criteria. Try clearing filters or import questions from Excel.
                </p>
                <button wire:click="openCreateModal" class="px-4 py-2 rounded-lg text-xs font-bold bg-[#C92535] text-white hover:bg-[#A81B29] transition">
                    Create New Question
                </button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="px-6 py-3.5 w-16">#ID</th>
                            <th class="px-6 py-3.5">Category</th>
                            <th class="px-6 py-3.5 min-w-[280px]">Question</th>
                            <th class="px-6 py-3.5 text-center">Key</th>
                            <th class="px-6 py-3.5 text-center">Difficulty</th>
                            <th class="px-6 py-3.5 text-center">Status</th>
                            <th class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @foreach ($questions as $q)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-6 py-4 font-mono text-slate-400">
                                    #{{ $q->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase"
                                          style="background-color: {{ $q->category->color }}15; color: {{ $q->category->color }}">
                                        {{ $q->category->name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900 line-clamp-2 max-w-lg">
                                        {{ $q->question_text }}
                                    </div>
                                    @if ($q->source)
                                        <div class="text-[11px] text-slate-400 mt-0.5 truncate">
                                            Source: {{ $q->source }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-800 font-bold text-xs">
                                        {{ $q->correct_choice }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    @php
                                        $diffVal = $q->difficulty instanceof \BackedEnum ? $q->difficulty->value : (string) $q->difficulty;
                                    @endphp
                                    @if ($diffVal === 'easy')
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Easy</span>
                                    @elseif ($diffVal === 'hard')
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">Hard</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">Medium</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    @php
                                        $statusVal = $q->status instanceof \BackedEnum ? $q->status->value : (string) $q->status;
                                    @endphp
                                    @if ($statusVal === 'published')
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">Published</span>
                                    @elseif ($statusVal === 'archived')
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">Archived</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">Draft</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button wire:click="openViewModal({{ $q->id }})"
                                                title="View Details"
                                                class="p-1.5 rounded-lg text-slate-500 hover:text-[#174A8B] hover:bg-slate-100 transition">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </button>
                                        <button wire:click="openEditModal({{ $q->id }})"
                                                title="Edit Question"
                                                class="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-slate-100 transition">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>
                                        <button wire:click="toggleArchive({{ $q->id }})"
                                                title="{{ $statusVal === 'archived' ? 'Restore' : 'Archive' }}"
                                                class="p-1.5 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-slate-100 transition">
                                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                        </button>
                                        <button wire:click="deleteQuestion({{ $q->id }})"
                                                wire:confirm="Are you sure you want to delete this question from the Question Bank?"
                                                title="Delete"
                                                class="p-1.5 rounded-lg text-slate-500 hover:text-red-600 hover:bg-slate-100 transition">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
                {{ $questions->links() }}
            </div>
        @endif
    </div>

    <!-- View Question Modal -->
    @if ($showViewModal && $viewingQuestion)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/60 transition-opacity" wire:click="closeViewModal"></div>

                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-6 pt-6 pb-4 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[11px] font-bold uppercase"
                                  style="background-color: {{ $viewingQuestion->category->color }}15; color: {{ $viewingQuestion->category->color }}">
                                {{ $viewingQuestion->category->name }}
                            </span>
                            <span class="text-xs text-slate-400 font-mono">Question #{{ $viewingQuestion->id }}</span>
                        </div>
                        <button wire:click="closeViewModal" class="text-slate-400 hover:text-slate-600 p-1">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Question Text -->
                        <div>
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Question</h4>
                            <p class="text-base font-semibold text-slate-900 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-200">
                                {{ $viewingQuestion->question_text }}
                            </p>
                        </div>

                        <!-- Choices -->
                        <div>
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Answer Choices</h4>
                            <div class="grid grid-cols-1 gap-2.5">
                                @foreach ($viewingQuestion->choices as $c)
                                    @php $isCorrect = ($c->choice_label === $viewingQuestion->correct_choice); @endphp
                                    <div class="flex items-start gap-3 p-3 rounded-xl border text-xs {{ $isCorrect ? 'bg-emerald-50/80 border-emerald-300 text-emerald-950 font-semibold' : 'bg-white border-slate-200 text-slate-700' }}">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full shrink-0 font-bold {{ $isCorrect ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $c->choice_label }}
                                        </span>
                                        <div class="flex-1 pt-0.5">
                                            {{ $c->choice_text }}
                                        </div>
                                        @if ($isCorrect)
                                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 uppercase">
                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                <span>Correct Answer</span>
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Explanation -->
                        @if ($viewingQuestion->explanation)
                            <div>
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Answer Explanation</h4>
                                <div class="p-4 rounded-xl bg-blue-50/70 border border-blue-100 text-xs text-blue-900 leading-relaxed">
                                    {{ $viewingQuestion->explanation }}
                                </div>
                            </div>
                        @endif

                        <!-- Metadata Footer -->
                        <div class="pt-4 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs text-slate-500">
                            <div>
                                <span class="block text-slate-400">Difficulty:</span>
                                <span class="font-semibold text-slate-800 capitalize">{{ $viewingQuestion->difficulty instanceof \BackedEnum ? $viewingQuestion->difficulty->value : (string) $viewingQuestion->difficulty }}</span>
                            </div>
                            <div>
                                <span class="block text-slate-400">Status:</span>
                                <span class="font-semibold text-slate-800 capitalize">{{ $viewingQuestion->status instanceof \BackedEnum ? $viewingQuestion->status->value : (string) $viewingQuestion->status }}</span>
                            </div>
                            <div>
                                <span class="block text-slate-400">Source:</span>
                                <span class="font-semibold text-slate-800 truncate block">{{ $viewingQuestion->source ?? 'Manual Entry' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-end">
                        <button wire:click="closeViewModal" class="px-4 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-100">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Create / Edit Question Modal -->
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/60 transition-opacity" wire:click="closeEditModal"></div>

                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-6 pt-6 pb-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900">
                            {{ $editingQuestionId ? 'Edit Question #' . $editingQuestionId : 'Add New Question' }}
                        </h3>
                        <button wire:click="closeEditModal" class="text-slate-400 hover:text-slate-600 p-1">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <form wire:submit="saveQuestion" class="p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- Category -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Category *</label>
                                <select wire:model="formCategoryId" class="block w-full py-2 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('formCategoryId') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Difficulty -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Difficulty *</label>
                                <select wire:model="formDifficulty" class="block w-full py-2 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                                    <option value="easy">Easy</option>
                                    <option value="medium">Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                                @error('formDifficulty') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Status *</label>
                                <select wire:model="formStatus" class="block w-full py-2 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                                    <option value="published">Published</option>
                                    <option value="draft">Draft</option>
                                    <option value="archived">Archived</option>
                                </select>
                                @error('formStatus') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Question Text -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Question Text *</label>
                            <textarea wire:model="formQuestionText" rows="3" required placeholder="Enter the complete question prompt..."
                                      class="block w-full p-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]"></textarea>
                            @error('formQuestionText') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- 4 Choices -->
                        <div class="space-y-2.5 pt-2">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Answer Choices (Choose Correct Answer) *</label>

                            <!-- Choice A -->
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" wire:model="formCorrectChoice" value="A" name="correctChoiceRadio" class="h-4 w-4 text-[#174A8B] focus:ring-[#2563EB]">
                                    <span class="font-bold text-xs text-slate-800">A</span>
                                </label>
                                <input type="text" wire:model="formChoiceA" required placeholder="Option A text..."
                                       class="flex-1 py-1.5 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                            </div>
                            @error('formChoiceA') <p class="text-[11px] text-red-600">{{ $message }}</p> @enderror

                            <!-- Choice B -->
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" wire:model="formCorrectChoice" value="B" name="correctChoiceRadio" class="h-4 w-4 text-[#174A8B] focus:ring-[#2563EB]">
                                    <span class="font-bold text-xs text-slate-800">B</span>
                                </label>
                                <input type="text" wire:model="formChoiceB" required placeholder="Option B text..."
                                       class="flex-1 py-1.5 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                            </div>
                            @error('formChoiceB') <p class="text-[11px] text-red-600">{{ $message }}</p> @enderror

                            <!-- Choice C -->
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" wire:model="formCorrectChoice" value="C" name="correctChoiceRadio" class="h-4 w-4 text-[#174A8B] focus:ring-[#2563EB]">
                                    <span class="font-bold text-xs text-slate-800">C</span>
                                </label>
                                <input type="text" wire:model="formChoiceC" required placeholder="Option C text..."
                                       class="flex-1 py-1.5 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                            </div>
                            @error('formChoiceC') <p class="text-[11px] text-red-600">{{ $message }}</p> @enderror

                            <!-- Choice D -->
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" wire:model="formCorrectChoice" value="D" name="correctChoiceRadio" class="h-4 w-4 text-[#174A8B] focus:ring-[#2563EB]">
                                    <span class="font-bold text-xs text-slate-800">D</span>
                                </label>
                                <input type="text" wire:model="formChoiceD" required placeholder="Option D text..."
                                       class="flex-1 py-1.5 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                            </div>
                            @error('formChoiceD') <p class="text-[11px] text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Explanation -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Answer Explanation (Optional)</label>
                            <textarea wire:model="formExplanation" rows="2" placeholder="Explain why the answer is correct for the reviewer..."
                                      class="block w-full p-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]"></textarea>
                        </div>

                        <!-- Source -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Source / Reference (Optional)</label>
                            <input type="text" wire:model="formSource" placeholder="e.g. 2026 CSC Exam Past Paper, RA 6713..."
                                   class="block w-full py-2 px-3 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-[#2563EB]">
                        </div>

                        <div class="bg-slate-50 -mx-6 -mb-6 px-6 py-4 border-t border-slate-100 flex justify-end gap-3 mt-6">
                            <button type="button" wire:click="closeEditModal" class="px-4 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-100">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2 rounded-lg text-xs font-bold text-white bg-[#C92535] hover:bg-[#A81B29] transition shadow-xs">
                                {{ $editingQuestionId ? 'Update Question' : 'Save Question' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== Delete All Questions Confirmation Modal ===== --}}
    @if ($showDeleteAllModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="delete-all-title">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-slate-900/60 transition-opacity" wire:click="cancelDeleteAll"></div>

                {{-- Modal Panel --}}
                <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full z-10 overflow-hidden">
                    {{-- Header --}}
                    <div class="flex items-start gap-4 p-6 border-b border-slate-100">
                        <div class="flex-shrink-0 w-11 h-11 rounded-full bg-red-100 flex items-center justify-center">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-[#C92535]"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 id="delete-all-title" class="text-sm font-bold text-slate-900">
                                Delete Entire Question Bank?
                            </h3>
                            <p class="mt-1 text-xs text-slate-500 leading-relaxed">
                                This will <strong class="text-slate-700">permanently delete every question</strong> and all
                                associated answer choices from the Question Bank repository.
                            </p>
                        </div>
                    </div>

                    {{-- Warning detail body --}}
                    <div class="px-6 py-4 space-y-3">
                        <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-800 leading-relaxed">
                            <strong class="block mb-1 font-bold uppercase tracking-wide text-red-700">⚠ This action cannot be undone</strong>
                            <ul class="list-disc list-inside space-y-1">
                                <li>All questions and their answer choices will be deleted.</li>
                                <li>Past exam attempt results and scores are <strong>not affected</strong> — historical snapshots are preserved.</li>
                                <li>Import batch records will remain for auditing purposes.</li>
                            </ul>
                        </div>
                        <p class="text-xs text-slate-500">
                            To start fresh, you can re-import questions from an Excel workbook after deletion.
                        </p>
                    </div>

                    {{-- Action footer --}}
                    <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-end gap-3">
                        <button type="button"
                                wire:click="cancelDeleteAll"
                                class="px-4 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-100 transition">
                            Cancel
                        </button>
                        <button type="button"
                                wire:click="deleteAllQuestions"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 px-5 py-2 rounded-lg text-xs font-bold text-white bg-[#C92535] hover:bg-[#A81B29] transition disabled:opacity-50 disabled:cursor-not-allowed shadow-xs">
                            <span wire:loading.remove wire:target="deleteAllQuestions">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5 inline -mt-0.5 mr-1"></i>
                                Yes, Delete All Questions
                            </span>
                            <span wire:loading wire:target="deleteAllQuestions" class="flex items-center gap-2">
                                <svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Deleting…
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
