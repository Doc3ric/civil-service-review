<div class="space-y-8">
    <!-- Header & Download Template Section -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Standardized Question Import Center</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Upload batch questions using Microsoft Excel (.xlsx) or CSV. Eliminate manual questionnaire encoding.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('questions.template.download') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#174A8B] hover:bg-[#0F3260] transition shadow-xs">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>Download Excel Template</span>
            </a>
            <a href="{{ route('questions.import.history') }}"
               class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                <i data-lucide="history" class="w-4 h-4"></i>
                <span>Import History</span>
            </a>
        </div>
    </div>

    <!-- Import Completed Summary -->
    @if ($importSummary)
        <div class="bg-emerald-50 rounded-2xl p-6 border border-emerald-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold">
                        <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-emerald-950">Batch Import Successfully Executed!</h3>
                        <p class="text-xs text-emerald-700">All valid questions have been permanently stored in MySQL.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if ($importSummary['invalid'] > 0 || $importSummary['duplicates'] > 0)
                        <a href="{{ route('questions.import.error-report', ['batch' => $activeBatchId]) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold text-rose-800 bg-rose-100 hover:bg-rose-200 transition">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                            <span>Download Error Report</span>
                        </a>
                    @endif
                    <a href="{{ route('questions.index') }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-bold text-white bg-[#174A8B] hover:bg-blue-900 transition">
                        <i data-lucide="database" class="w-3.5 h-3.5"></i>
                        <span>View Question Bank</span>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-2">
                <div class="bg-white p-3 rounded-xl border border-emerald-100 text-center">
                    <span class="text-[11px] font-semibold text-slate-500 block">Total Rows</span>
                    <span class="text-xl font-bold text-slate-800">{{ $importSummary['total'] }}</span>
                </div>
                <div class="bg-white p-3 rounded-xl border border-emerald-100 text-center">
                    <span class="text-[11px] font-semibold text-emerald-600 block">Imported</span>
                    <span class="text-xl font-bold text-emerald-700">{{ $importSummary['imported'] }}</span>
                </div>
                <div class="bg-white p-3 rounded-xl border border-emerald-100 text-center">
                    <span class="text-[11px] font-semibold text-amber-600 block">Duplicates Skipped</span>
                    <span class="text-xl font-bold text-amber-700">{{ $importSummary['duplicates'] }}</span>
                </div>
                <div class="bg-white p-3 rounded-xl border border-emerald-100 text-center">
                    <span class="text-[11px] font-semibold text-rose-600 block">Invalid Rows</span>
                    <span class="text-xl font-bold text-rose-700">{{ $importSummary['invalid'] }}</span>
                </div>
                <div class="bg-white p-3 rounded-xl border border-emerald-100 text-center">
                    <span class="text-[11px] font-semibold text-slate-500 block">Total Skipped</span>
                    <span class="text-xl font-bold text-slate-700">{{ $importSummary['skipped'] }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Upload Card (If no active preview batch or importing again) -->
    @if (!$activeBatch || $activeBatch->status === \App\Enums\ImportStatus::Completed)
        <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-xs">
            <h3 class="text-base font-bold text-slate-900 mb-2">Step 1 — Upload Examination Questions Workbook</h3>
            <p class="text-xs text-slate-500 leading-relaxed mb-6 max-w-2xl">
                Please upload your spreadsheet (.xlsx or .csv) adhering to the expected template headers:
                <code class="px-1.5 py-0.5 rounded bg-slate-100 text-[#174A8B] font-mono text-[11px]">category</code>,
                <code class="px-1.5 py-0.5 rounded bg-slate-100 text-[#174A8B] font-mono text-[11px]">question</code>,
                <code class="px-1.5 py-0.5 rounded bg-slate-100 text-[#174A8B] font-mono text-[11px]">choice_a</code>,
                <code class="px-1.5 py-0.5 rounded bg-slate-100 text-[#174A8B] font-mono text-[11px]">choice_b</code>,
                <code class="px-1.5 py-0.5 rounded bg-slate-100 text-[#174A8B] font-mono text-[11px]">choice_c</code>,
                <code class="px-1.5 py-0.5 rounded bg-slate-100 text-[#174A8B] font-mono text-[11px]">choice_d</code>,
                <code class="px-1.5 py-0.5 rounded bg-slate-100 text-[#174A8B] font-mono text-[11px]">correct_answer</code>.
            </p>

            <form wire:submit="processUpload" class="space-y-4">
                <div class="border-2 border-dashed border-slate-300 rounded-2xl p-8 text-center hover:border-[#174A8B] transition bg-slate-50/50">
                    <div class="w-14 h-14 mx-auto rounded-full bg-blue-50 text-[#174A8B] flex items-center justify-center mb-3">
                        <i data-lucide="file-spreadsheet" class="w-7 h-7"></i>
                    </div>

                    <div class="text-sm font-semibold text-slate-700">
                        <label for="file-upload" class="relative cursor-pointer text-[#2563EB] hover:text-[#1D4ED8] focus-within:outline-none font-bold">
                            <span>Click to browse Excel/CSV file</span>
                            <input id="file-upload" wire:model="file" type="file" accept=".xlsx,.csv,.txt" class="sr-only">
                        </label>
                        <span class="text-slate-500 font-normal"> or drag and drop</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">
                        Supported: .xlsx, .csv up to 10MB
                    </p>

                    @if ($file)
                        <div class="mt-4 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-100/70 text-[#174A8B] text-xs font-semibold">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Selected: {{ $file->getClientOriginalName() }} ({{ round($file->getSize() / 1024, 1) }} KB)</span>
                        </div>
                    @endif
                </div>

                @error('file')
                    <p class="text-xs text-red-600 font-semibold">{{ $message }}</p>
                @enderror

                <div class="flex justify-end">
                    <button type="submit"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl font-bold text-xs text-white bg-[#C92535] hover:bg-[#A81B29] transition shadow-xs disabled:opacity-50">
                        <span wire:loading.remove wire:target="processUpload">Upload and Validate Preview</span>
                        <span wire:loading wire:target="processUpload">Processing Spreadsheet...</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Preview & Validation Stage (If active preview batch exists) -->
    @if ($activeBatch && $activeBatch->status !== \App\Enums\ImportStatus::Completed)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden space-y-6">
            <!-- Batch Header & Confirmation Bar -->
            <div class="p-6 border-b border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded bg-blue-100 text-blue-800">Preview Mode</span>
                        <h3 class="text-base font-bold text-slate-900">{{ $activeBatch->original_filename }}</h3>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Reviewed {{ $counts['all'] }} total rows • {{ $counts['valid'] }} valid • {{ $counts['duplicate'] }} duplicates • {{ $counts['invalid'] }} invalid
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button wire:click="cancelPreview"
                            class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-100 transition">
                        Cancel
                    </button>

                    @if ($counts['valid'] > 0)
                        <button wire:click="confirmImport"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-[#C92535] hover:bg-[#A81B29] transition shadow-xs disabled:opacity-50">
                            <span wire:loading.remove wire:target="confirmImport">Confirm Import of {{ $counts['valid'] }} Valid Questions</span>
                            <span wire:loading wire:target="confirmImport">Importing to Database...</span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Tab Filters -->
            <div class="px-6 flex items-center gap-2 border-b border-slate-200 pb-3">
                <button wire:click="$set('rowFilter', 'all')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $rowFilter === 'all' ? 'bg-[#174A8B] text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    All Rows ({{ $counts['all'] }})
                </button>
                <button wire:click="$set('rowFilter', 'valid')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $rowFilter === 'valid' ? 'bg-emerald-600 text-white' : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}">
                    Valid ({{ $counts['valid'] }})
                </button>
                <button wire:click="$set('rowFilter', 'duplicate')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $rowFilter === 'duplicate' ? 'bg-amber-600 text-white' : 'text-amber-700 bg-amber-50 hover:bg-amber-100' }}">
                    Duplicate ({{ $counts['duplicate'] }})
                </button>
                <button wire:click="$set('rowFilter', 'invalid')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $rowFilter === 'invalid' ? 'bg-rose-600 text-white' : 'text-rose-700 bg-rose-50 hover:bg-rose-100' }}">
                    Invalid ({{ $counts['invalid'] }})
                </button>
            </div>

            <!-- Preview Rows Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="px-6 py-3 text-center w-16">Row #</th>
                            <th class="px-6 py-3">Category</th>
                            <th class="px-6 py-3 min-w-[280px]">Question & Choices</th>
                            <th class="px-6 py-3 text-center">Correct</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3">Validation Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @forelse ($previewRows as $row)
                            @php
                                $statusVal = $row->status instanceof \BackedEnum ? $row->status->value : (string) $row->status;
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition {{ $statusVal === 'invalid' ? 'bg-rose-50/30' : ($statusVal === 'duplicate' ? 'bg-amber-50/30' : '') }}">
                                <td class="px-6 py-4 font-mono font-bold text-center text-slate-500">
                                    {{ $row->row_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-semibold">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase bg-slate-100 text-slate-800">
                                        {{ $row->category_code ?: 'MISSING' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900 mb-1">
                                        {{ $row->question_text ?: '[Empty Question Text]' }}
                                    </div>
                                    <div class="grid grid-cols-2 gap-1 text-[11px] text-slate-500">
                                        <div><span class="font-bold text-slate-700">A:</span> {{ Str::limit($row->choice_a, 40) }}</div>
                                        <div><span class="font-bold text-slate-700">B:</span> {{ Str::limit($row->choice_b, 40) }}</div>
                                        <div><span class="font-bold text-slate-700">C:</span> {{ Str::limit($row->choice_c, 40) }}</div>
                                        <div><span class="font-bold text-slate-700">D:</span> {{ Str::limit($row->choice_d, 40) }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap font-bold">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-800 text-xs">
                                        {{ $row->correct_answer ?: '?' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    @if ($statusVal === 'valid')
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                            Valid
                                        </span>
                                    @elseif ($statusVal === 'duplicate')
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                                            Duplicate
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800">
                                            Invalid
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    @if ($row->error_messages_json)
                                        <ul class="text-rose-700 list-disc list-inside space-y-0.5">
                                            @foreach ($row->error_messages_json as $err)
                                                <li>{{ $err }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-emerald-700 font-medium">Ready to import</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-500">
                                    No rows found matching current filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
                {{ $previewRows->links() }}
            </div>
        </div>
    @endif
</div>
