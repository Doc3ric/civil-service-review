<div class="space-y-8"
     x-data="{
         initCharts() {
             const trendLabels = @js($analytics['chart_trends']['labels']);
             const trendScores = @js($analytics['chart_trends']['scores']);
             const catLabels = @js($analytics['chart_categories']['labels']);
             const catScores = @js($analytics['chart_categories']['scores']);
             const catColors = @js($analytics['chart_categories']['colors']);

             // Score Trends Line Chart
             const trendCtx = document.getElementById('trendChart');
             if (trendCtx && trendLabels.length > 0) {
                 new Chart(trendCtx, {
                     type: 'line',
                     data: {
                         labels: trendLabels,
                         datasets: [
                             {
                                 label: 'Score Percentage',
                                 data: trendScores,
                                 borderColor: '#174A8B',
                                 backgroundColor: 'rgba(23, 74, 139, 0.08)',
                                 fill: true,
                                 tension: 0.35,
                                 pointBackgroundColor: '#C92535',
                                 pointBorderColor: '#FFFFFF',
                                 pointBorderWidth: 2,
                                 pointRadius: 5,
                             },
                             {
                                 label: 'Passing Threshold (80%)',
                                 data: trendLabels.map(() => 80),
                                 borderColor: '#C92535',
                                 borderDash: [5, 5],
                                 borderWidth: 1.5,
                                 pointRadius: 0,
                                 fill: false,
                             }
                         ]
                     },
                     options: {
                         responsive: true,
                         maintainAspectRatio: false,
                         scales: {
                             y: {
                                 min: 0,
                                 max: 100,
                                 ticks: {
                                     callback: function(value) { return value + '%'; }
                                 }
                             }
                         },
                         plugins: {
                             legend: { position: 'top' }
                         }
                     }
                 });
             }

             // Category Performance Bar Chart
             const catCtx = document.getElementById('categoryChart');
             if (catCtx) {
                 new Chart(catCtx, {
                     type: 'bar',
                     data: {
                         labels: catLabels,
                         datasets: [{
                             label: 'Category Accuracy',
                             data: catScores,
                             backgroundColor: catColors,
                             borderRadius: 8,
                         }]
                     },
                     options: {
                         responsive: true,
                         maintainAspectRatio: false,
                         scales: {
                             y: {
                                 min: 0,
                                 max: 100,
                                 ticks: {
                                     callback: function(value) { return value + '%'; }
                                 }
                             }
                         },
                         plugins: {
                             legend: { display: false }
                         }
                     }
                 });
             }
         }
     }"
     x-init="initCharts()">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Comprehensive Readiness Analytics</h2>
            <p class="text-xs text-slate-500">In-depth statistical breakdown of test accuracy, consistency, and examination readiness</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('exams.full') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#C92535] hover:bg-[#A81B29] transition shadow-xs">
                <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                <span>Take Full 150 Exam</span>
            </a>
        </div>
    </div>

    <!-- 4 High-Level Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Passing Rate</span>
                <span class="text-2xl font-extrabold text-slate-900 mt-1 block">{{ number_format($analytics['passing_rate'], 1) }}%</span>
                <span class="text-[11px] text-slate-400">Score &ge; 80% Benchmark</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-[#174A8B] flex items-center justify-center">
                <i data-lucide="award" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Mean Score</span>
                <span class="text-2xl font-extrabold text-slate-900 mt-1 block">{{ number_format($analytics['average_score'], 1) }}%</span>
                <span class="text-[11px] text-slate-400">Across {{ $analytics['total_exams_completed'] }} exam attempts</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i data-lucide="trending-up" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Strongest Subject</span>
                <span class="text-base font-bold text-slate-900 mt-1 block truncate max-w-[150px]">{{ $analytics['strongest_category'] ?? 'N/A' }}</span>
                <span class="text-[11px] text-emerald-600 font-semibold">Highest accuracy</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <i data-lucide="check-circle-2" class="w-6 h-6"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Focus Needed</span>
                <span class="text-base font-bold text-[#C92535] mt-1 block truncate max-w-[150px]">{{ $analytics['weakest_category'] ?? 'N/A' }}</span>
                <span class="text-[11px] text-rose-600 font-semibold">Recommended for practice</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-[#C92535] flex items-center justify-center">
                <i data-lucide="alert-circle" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Chart.js Visualizations (2 Cols) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Score Trend Chart -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Score Progression Over Time</h3>
                    <p class="text-xs text-slate-500">Historical performance with 80% passing target line</p>
                </div>
            </div>

            <div class="h-64 relative">
                @if (empty($analytics['chart_trends']['labels']))
                    <div class="h-full flex flex-col items-center justify-center text-center p-6 text-slate-400">
                        <i data-lucide="trending-up" class="w-8 h-8 mb-2"></i>
                        <p class="text-xs">Take your first examination to generate score progression trends.</p>
                    </div>
                @else
                    <canvas id="trendChart"></canvas>
                @endif
            </div>
        </div>

        <!-- Category Accuracy Chart -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Subject Category Accuracy Matrix</h3>
                    <p class="text-xs text-slate-500">Comparative accuracy percentage across 4 test categories</p>
                </div>
            </div>

            <div class="h-64 relative">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Detailed Subject Performance Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between bg-slate-50/60">
            <div>
                <h3 class="text-base font-bold text-slate-900">Subject Performance & Question Bank Capacity</h3>
                <p class="text-xs text-slate-500">Comprehensive breakdown of total solved questions and accuracy by domain</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-semibold tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Category</th>
                        <th class="px-6 py-3.5 text-center">Questions in Bank</th>
                        <th class="px-6 py-3.5 text-center">Attempted Items</th>
                        <th class="px-6 py-3.5 text-center">Correct Answers</th>
                        <th class="px-6 py-3.5 text-center">Accuracy</th>
                        <th class="px-6 py-3.5 text-right">Quick Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @foreach ($analytics['category_stats'] as $code => $cat)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4 font-bold text-slate-900 flex items-center gap-2.5">
                                <span class="w-3.5 h-3.5 rounded-full" style="background-color: {{ $cat['color'] }}"></span>
                                <span>{{ $cat['name'] }}</span>
                            </td>
                            <td class="px-6 py-4 text-center font-semibold text-slate-700">
                                {{ $cat['bank_count'] }}
                            </td>
                            <td class="px-6 py-4 text-center font-semibold text-slate-700">
                                {{ $cat['attempted_count'] }}
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-emerald-700">
                                {{ $cat['correct_count'] }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="inline-flex items-center gap-2">
                                    <span class="font-bold text-slate-900 text-sm">{{ number_format($cat['percentage'], 1) }}%</span>
                                    <div class="w-16 h-2 rounded-full bg-slate-100 overflow-hidden hidden sm:block">
                                        <div class="h-full rounded-full" style="width: {{ $cat['percentage'] }}%; background-color: {{ $cat['color'] }}"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('exams.category', ['category_id' => \App\Models\ExamCategory::where('code', $code)->value('id')]) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-white transition shadow-xs"
                                   style="background-color: {{ $cat['color'] }}">
                                    <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i>
                                    <span>Practice</span>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
