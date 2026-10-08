<?php

use App\Http\Controllers\AIController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:ai:tools'])
    ->prefix('ai')
    ->group(function (): void {
        Route::post('/chat', [AIController::class, 'chat'])
            ->middleware('throttle:10,1')
            ->name('ai.chat');
    });
