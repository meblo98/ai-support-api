<?php

use App\Http\Controllers\AIController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('abilities:tickets:read')->group(function (): void {
        Route::get('/tickets', [TicketController::class, 'index']);
        Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
    });

    Route::middleware('abilities:tickets:write')->group(function (): void {
        Route::post('/tickets', [TicketController::class, 'store']);
        Route::patch('/tickets/{ticket}', [TicketController::class, 'update']);
        Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy']);
    });

    Route::prefix('ai')->middleware('abilities:ai:tools')->group(function (): void {
        Route::get('/tools', [AIController::class, 'tools']);
        Route::post('/tools/{tool}', [AIController::class, 'execute']);
    });
});
