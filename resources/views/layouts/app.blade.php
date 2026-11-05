<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' - Civil Service Review 2027' : 'Civil Service Review 2027' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full antialiased font-sans text-slate-800 bg-slate-50" x-data="{ sidebarOpen: false }">
    <div class="min-h-full flex flex-col lg:flex-row">
        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen"
             x-cloak
             x-transition:enter="transition-opacity ease-linear duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-40 bg-slate-900/60 lg:hidden"
             @click="sidebarOpen = false"></div>

        <!-- Sidebar Navigation -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="fixed inset-y-0 left-0 z-50 w-72 bg-[#174A8B] text-white transition-transform duration-200 ease-in-out lg:static lg:inset-auto lg:translate-x-0 flex flex-col justify-between shadow-xl lg:shadow-none">
            
            <!-- Branding Header -->
            <div class="px-6 py-6 border-b border-blue-900/60 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg bg-[#C92535] flex items-center justify-center font-bold text-white shadow-md ring-2 ring-white/20">
                        <span class="text-lg tracking-wider">CSC</span>
                    </div>
                    <div>
                        <div class="font-bold text-base tracking-wide text-white flex items-center gap-1.5">
                            <span>REVIEW 2027</span>
                            <span class="inline-block w-2 h-2 rounded-full bg-[#C92535]"></span>
                        </div>
                        <div class="text-[11px] text-blue-200 uppercase tracking-widest font-semibold">Professional Exam</div>
                    </div>
                </div>
                <button @click="sidebarOpen = false" class="lg:hidden text-blue-200 hover:text-white p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-4 py-5 overflow-y-auto exam-scroll space-y-6">
                <!-- MAIN -->
                <div>
                    <div class="px-3 mb-2 text-[11px] font-bold text-blue-300/80 uppercase tracking-wider">Main</div>
                    <div class="space-y-1">
                        <a href="{{ route('dashboard') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-[#C92535] text-white shadow' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
                            <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>
                </div>

                <!-- QUESTION MANAGEMENT -->
                <div>
                    <div class="px-3 mb-2 text-[11px] font-bold text-blue-300/80 uppercase tracking-wider">Question Management</div>
                    <div class="space-y-1">
                        <a href="{{ route('questions.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('questions.index*') && !request()->routeIs('questions.import*') ? 'bg-[#C92535] text-white shadow' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
                            <i data-lucide="database" class="w-4 h-4"></i>
                            <span>Question Bank</span>
                        </a>
                        <a href="{{ route('questions.import') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('questions.import') ? 'bg-[#C92535] text-white shadow' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
                            <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                            <span>Import Questions</span>
                        </a>
                        <a href="{{ route('categories.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('categories.*') ? 'bg-[#C92535] text-white shadow' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
                            <i data-lucide="folder-kanban" class="w-4 h-4"></i>
                            <span>Categories</span>
                        </a>
                        <a href="{{ route('questions.import.history') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('questions.import.history*') ? 'bg-[#C92535] text-white shadow' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
                            <i data-lucide="history" class="w-4 h-4"></i>
                            <span>Import History</span>
                        </a>
                    </div>
                </div>

                <!-- EXAMINATION -->
                <div>
                    <div class="px-3 mb-2 text-[11px] font-bold text-blue-300/80 uppercase tracking-wider">Examination</div>
                    <div class="space-y-1">
                        <a href="{{ route('exams.full') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('exams.full') ? 'bg-[#C92535] text-white shadow' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
                            <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                            <span>Full Practice (150 Items)</span>
                        </a>
                        <a href="{{ route('exams.category') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('exams.category') ? 'bg-[#C92535] text-white shadow' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
                            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                            <span>Category Practice</span>
                        </a>
                        <a href="{{ route('exams.history') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('exams.history') ? 'bg-[#C92535] text-white shadow' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
                            <i data-lucide="history" class="w-4 h-4"></i>
                            <span>Examination History</span>
                        </a>
                    </div>
                </div>

                <!-- MY PROGRESS -->
                <div>
                    <div class="px-3 mb-2 text-[11px] font-bold text-blue-300/80 uppercase tracking-wider">My Progress</div>
                    <div class="space-y-1">
                        <a href="{{ route('analytics') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('analytics') ? 'bg-[#C92535] text-white shadow' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
                            <i data-lucide="trending-up" class="w-4 h-4"></i>
                            <span>Performance Analytics</span>
                        </a>
                        <a href="{{ route('streak-calendar') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('streak-calendar') ? 'bg-[#C92535] text-white shadow' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
                            <i data-lucide="calendar-days" class="w-4 h-4"></i>
                            <span>Streak Calendar</span>
                        </a>
                    </div>
                </div>

                <!-- PREFERENCES -->
                <div>
                    <div class="px-3 mb-2 text-[11px] font-bold text-blue-300/80 uppercase tracking-wider">Preferences</div>
                    <div class="space-y-1">
                        <a href="{{ route('settings') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('settings') ? 'bg-[#C92535] text-white shadow' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
                            <i data-lucide="settings" class="w-4 h-4"></i>
                            <span>Settings</span>
                        </a>
                    </div>
                </div>
            </nav>

            <!-- User Footer in Sidebar -->
            <div class="p-4 border-t border-blue-900/60 bg-[#0F3260]/60">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 truncate">
                        <div class="w-9 h-9 rounded-full bg-blue-700/80 border border-white/20 flex items-center justify-center font-bold text-xs text-white uppercase">
                            {{ substr(auth()->user()->name ?? 'U', 0, 2) }}
                        </div>
                        <div class="truncate">
                            <div class="text-sm font-medium text-white truncate">{{ auth()->user()->name ?? 'User' }}</div>
                            <div class="text-xs text-blue-200 truncate">{{ auth()->user()->email ?? '' }}</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Log Out" class="p-2 text-blue-200 hover:text-red-400 rounded-lg hover:bg-white/10 transition">
                            <i data-lucide="log-out" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Header -->
            <header class="bg-white border-b border-slate-200 sticky top-0 z-30 px-4 sm:px-6 lg:px-8 py-3.5 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 text-slate-600 hover:text-slate-900 rounded-lg hover:bg-slate-100">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-slate-900">{{ $header ?? $title ?? 'Civil Service Review 2027' }}</h1>
                        @if (isset($breadcrumbs))
                            <div class="text-xs text-slate-500 mt-0.5">{{ $breadcrumbs }}</div>
                        @endif
                    </div>
                </div>

                <!-- Header Actions & Streak Pill -->
                <div class="flex items-center gap-3">
                    @php
                        $user = auth()->user();
                        $streakCount = 0;
                        if ($user) {
                            $streakService = app(\App\Services\Progress\StreakCalculationService::class);
                            $streaks = $streakService->calculateStreaks($user);
                            $streakCount = $streaks['current_streak'];
                        }
                    @endphp

                    <a href="{{ route('streak-calendar') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold {{ $streakCount > 0 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }} hover:shadow-xs transition">
                        <i data-lucide="flame" class="w-4 h-4 {{ $streakCount > 0 ? 'text-amber-500 fill-amber-500' : 'text-slate-400' }}"></i>
                        <span>{{ $streakCount }} {{ Str::plural('Day', $streakCount) }} Streak</span>
                    </a>

                    <a href="{{ route('exams.full') }}"
                       class="hidden sm:inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-[#C92535] text-white hover:bg-[#A81B29] transition shadow-xs">
                        <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                        <span>Start 150 Exam</span>
                    </a>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <!-- Toast/Flash Messages -->
                @if (session()->has('success'))
                    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between text-sm shadow-xs">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                @if (session()->has('error'))
                    <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between text-sm shadow-xs">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600"></i>
                            <span>{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
