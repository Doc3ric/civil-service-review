<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Batch Import Audit Logs</h2>
            <p class="text-xs text-slate-500">Historical records of uploaded Excel workbooks and question encoding batches</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('questions.import') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#C92535] hover:bg-[#A81B29] transition shadow-xs">
                <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                <span>Import New File</span>
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        @if ($batches->isEmpty())
            <div class="p-12 text-center">
                <div class="w-16 h-16 mx-auto rounded-full bg-blue-50 text-[#174A8B] flex items-center justify-center mb-3">
                    <i data-lucide="history" class="w-8 h-8"></i>
                </div>
                <h4 class="text-sm font-bold text-slate-800 mb-1">No Import Batches Found</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mb-4">
                    You haven't imported any Excel question files yet. Use the standardized template to upload your first batch.
                </p>
                <a href="{{ route('questions.import') }}" class="px-4 py-2 rounded-lg text-xs font-bold bg-[#174A8B] text-white hover:bg-blue-900 transition">
                    Go to Import Center
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="px-6 py-3.5 w-16">Batch #</th>
                            <th class="px-6 py-3.5">Original File</th>
                            <th class="px-6 py-3.5">Date Uploaded</th>
                            <th class="px-6 py-3.5 text-center">Total Rows</th>
                            <th class="px-6 py-3.5 text-center">Imported</th>
                            <th class="px-6 py-3.5 text-center">Duplicates</th>
                            <th class="px-6 py-3.5 text-center">Invalid</th>
                            <th class="px-6 py-3.5 text-center">Status</th>
                            <th class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @foreach ($batches as $b)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-6 py-4 font-mono font-bold text-slate-400">
                                    #{{ $b->id }}
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-900">
                                    {{ $b->original_filename }}
                                </td>
                                <td class="px-6 py-4 text-slate-500 whitespace-nowrap">
                                    {{ $b->created_at->format('M d, Y h:i A') }}
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-slate-800">
                                    {{ $b->total_rows }}
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-emerald-700">
                                    {{ $b->imported_rows }}
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-amber-700">
                                    {{ $b->duplicate_rows }}
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-rose-700">
                                    {{ $b->invalid_rows }}
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    @php
                                        $bStatus = $b->status instanceof \BackedEnum ? $b->status->value : (string) $b->status;
                                    @endphp
                                    @if ($bStatus === 'completed')
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">Completed</span>
                                    @elseif ($bStatus === 'preview')
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800">In Preview</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700">{{ ucfirst($bStatus) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        @if ($bStatus === 'preview')
                                            <a href="{{ route('questions.import', ['batch' => $b->id]) }}"
                                               class="px-2.5 py-1 rounded text-xs font-bold text-white bg-[#174A8B] hover:bg-blue-900 transition">
                                                Resume Preview
                                            </a>
                                        @endif

                                        @if ($b->invalid_rows > 0 || $b->duplicate_rows > 0)
                                            <a href="{{ route('questions.import.error-report', ['batch' => $b->id]) }}"
                                               title="Download Error Report"
                                               class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition">
                                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                                <span>Errors</span>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
                {{ $batches->links() }}
            </div>
        @endif
    </div>
</div>
