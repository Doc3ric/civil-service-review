<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ImportExportController;
use App\Livewire\Analytics\PerformanceAnalyticsDashboard;
use App\Livewire\Dashboard\DashboardOverview;
use App\Livewire\Examinations\CategoryPracticeStarter;
use App\Livewire\Examinations\ExamHistoryList;
use App\Livewire\Examinations\ExamRunner;
use App\Livewire\Examinations\FullExamStarter;
use App\Livewire\Questions\CategoryList;
use App\Livewire\Questions\ImportHistoryList;
use App\Livewire\Questions\QuestionImportManager;
use App\Livewire\Questions\QuestionList;
use App\Livewire\Results\ExamResultViewer;
use App\Livewire\Settings\SettingsManager;
use App\Livewire\Streaks\StreakCalendarView;
use Illuminate\Support\Facades\Route;

// Root redirect
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Authentication
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Authenticated Application Routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', DashboardOverview::class)->name('dashboard');

    // Question Management
    Route::get('/questions', QuestionList::class)->name('questions.index');
    Route::get('/categories', CategoryList::class)->name('categories.index');
    Route::get('/questions/import', QuestionImportManager::class)->name('questions.import');
    Route::get('/questions/import/{batch}', QuestionImportManager::class)->name('questions.import.batch');
    Route::get('/questions/template/download', [ImportExportController::class, 'downloadTemplate'])->name('questions.template.download');
    Route::get('/questions/import-history', ImportHistoryList::class)->name('questions.import.history');
    Route::get('/questions/import/{batch}/error-report', [ImportExportController::class, 'downloadErrorReport'])->name('questions.import.error-report');

    // Examinations
    Route::get('/exams/full', FullExamStarter::class)->name('exams.full');
    Route::get('/exams/category', CategoryPracticeStarter::class)->name('exams.category');
    Route::get('/exams/history', ExamHistoryList::class)->name('exams.history');
    Route::get('/exams/{attempt}', ExamRunner::class)->name('exams.runner');

    // Results & Scoring
    Route::get('/results/{attempt}', ExamResultViewer::class)->name('results.show');

    // Progress & Analytics
    Route::get('/analytics', PerformanceAnalyticsDashboard::class)->name('analytics');
    Route::get('/streak-calendar', StreakCalendarView::class)->name('streak-calendar');

    // Preferences & Settings
    Route::get('/settings', SettingsManager::class)->name('settings');
});
