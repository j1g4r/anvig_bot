<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IncomingWebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhook/{conversation}', [IncomingWebhookController::class, 'handle']);

// Learning & Continuous Improvement API
Route::prefix('learning')->group(function () {
    Route::get('/examples', [\App\Http\Controllers\LearningController::class, 'examples']);
    Route::post('/feedback', [\App\Http\Controllers\LearningController::class, 'submitFeedback']);
    Route::get('/insights/{agent}', [\App\Http\Controllers\LearningController::class, 'insights']);
    Route::post('/train/{agent}', [\App\Http\Controllers\LearningController::class, 'train']);
    Route::get('/sessions/{agent}', [\App\Http\Controllers\LearningController::class, 'sessions']);
    Route::get('/adaptations/{agent}', [\App\Http\Controllers\LearningController::class, 'adaptations']);
    Route::patch('/adaptations/{id}/toggle', [\App\Http\Controllers\LearningController::class, 'toggleAdaptation']);
    Route::delete('/adaptations/{id}', [\App\Http\Controllers\LearningController::class, 'deleteAdaptation']);
});

// Voice Interface API
Route::prefix("voice")->group(function () {
    Route::post("/transcribe", [VoiceSessionController::class, "transcribe"]);
    Route::post("/speak", [VoiceSessionController::class, "speak"]);
    Route::get("/voices", [VoiceSessionController::class, "voices"]);
    Route::get("/health", [VoiceSessionController::class, "health"]);
    Route::get("/sessions/{uuid}", [VoiceSessionController::class, "show"]);
    Route::post("/sessions/{uuid}/submit-audio", [VoiceSessionController::class, "submitAudio"]);
});
