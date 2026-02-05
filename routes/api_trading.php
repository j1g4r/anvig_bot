<?php

use App\Http\Controllers\Trading\TradingController;
use Illuminate\Support\Facades\Route;

Route::prefix('trading')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    
    // Portfolio
    Route::get('/portfolio', [TradingController::class, 'portfolio']);
    Route::get('/portfolio/stats', [TradingController::class, 'stats']);
    
    // Trades
    Route::get('/trades', [TradingController::class, 'trades']);
    Route::post('/trades/{id}/close', [TradingController::class, 'closeTrade']);
    
    // Strategies
    Route::get('/strategies', [TradingController::class, 'strategies']);
    Route::post('/strategies/{id}/toggle', [TradingController::class, 'toggleStrategy']);
    Route::post('/strategies/run', [TradingController::class, 'runStrategies']);
    
});
