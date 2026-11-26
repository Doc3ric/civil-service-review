<?php

declare(strict_types=1);

use App\Http\Controllers\Api\MobileQuestionBankController;
use App\Http\Controllers\Api\MobilePracticeAttemptController;
use App\Http\Controllers\Api\MobileProgressController;
use App\Http\Controllers\Api\MobileSessionController;
use Illuminate\Support\Facades\Route;

Route::post('/mobile/session', [MobileSessionController::class, 'store'])->middleware('throttle:6,1');

Route::middleware(['mobile-token', 'throttle:60,1'])->prefix('mobile')->group(function (): void {
    Route::delete('/session', [MobileSessionController::class, 'destroy']);
    Route::get('/question-bank', MobileQuestionBankController::class);
    Route::post('/attempts', [MobilePracticeAttemptController::class, 'store'])->middleware('throttle:30,1');
    Route::get('/progress', MobileProgressController::class);
});
