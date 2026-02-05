<?php

declare(strict_types=1);

use App\Events\ChatMessage;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChatSessionController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MCP\McpWebhookController;
use App\Http\Controllers\Admin\LlmProviderConfigController;
use App\Http\Controllers\VisionController;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CortexController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'service' => 'anvig-api']);
});

/*
|--------------------------------------------------------------------------
| Chat Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'chat', 'middleware' => ['auth:api', 'throttle:api']], function () {
    Route::post('/send', [MessageController::class, 'send'])->name('api.chat.send');
    Route::get('/messages/{token}', [MessageController::class, 'fetch'])->name('api.chat.messages');
});

Route::get('/chat/test-broadcast-stream', function () {
    return view('test-broadcast-stream');
});

Route::match(['get', 'post'], '/chat/broadcast', function (Request $request) {
    $message = $request->input('message', 'Hello from API at '.now());
    broadcast(new ChatMessage($message))->toOthers();
    return response()->json(['broadcasted' => true]);
});

Route::apiResource('chat-sessions', ChatSessionController::class)->middleware('auth:api');

/*
|--------------------------------------------------------------------------
| MCP Routes
|--------------------------------------------------------------------------
*/

Route::post('mcp/webhook/{context}', [McpWebhookController::class, 'handle'])
    ->name('api.mcp.webhook');

/*
|--------------------------------------------------------------------------
| LLM Provider Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin/llm')->middleware(['auth:api'])->group(function () {
    Route::get('/providers', [LlmProviderConfigController::class, 'index']);
    Route::post('/providers', [LlmProviderConfigController::class, 'store']);
    Route::put('/providers/{id}', [LlmProviderConfigController::class, 'update']);
    Route::patch('/providers/{id}/enable', [LlmProviderConfigController::class, 'enable']);
    Route::patch('/providers/{id}/disable', [LlmProviderConfigController::class, 'disable']);
});

/*
|--------------------------------------------------------------------------
| Vision 2.0 Real-time Stream Routes
|--------------------------------------------------------------------------
*/

Route::prefix('vision')->group(function () {
    // Stream management
    Route::post('/stream/start', [VisionController::class, 'startStream'])
        ->name('api.vision.stream.start');
    Route::post('/stream/ingest', [VisionController::class, 'ingestFrame'])
        ->name('api.vision.stream.ingest');
    Route::post('/stream/stop', [VisionController::class, 'stopStream'])
        ->name('api.vision.stream.stop');
    
    // Query endpoints
    Route::get('/stream/{stream_id}', [VisionController::class, 'getStreamStatus'])
        ->name('api.vision.stream.status');
    Route::get('/session/{session_id}/frames', [VisionController::class, 'getSessionFrames'])
        ->name('api.vision.session.frames');
    
    // Legacy MCP-compliant endpoints
    Route::post('/analyze-image', function (Request $request) {
        return app(\App\MCP\Tools\VisionTool::class)
            ->analyzeFrame($request->input('image'), $request->input('context'));
    })->name('api.vision.analyze_image');
});

Route::prefix('v1')->group(function () {
    Route::prefix('vision')->group(function () {
        Route::post('/stream/start', [VisionController::class, 'startStream'])
            ->name('api.v1.vision.stream.start');
        Route::post('/stream/ingest', [VisionController::class, 'ingestFrame'])
            ->name('api.v1.vision.stream.ingest');
        Route::post('/stream/stop', [VisionController::class, 'stopStream'])
            ->name('api.v1.vision.stream.stop');
        Route::get('/stream/{stream_id}', [VisionController::class, 'getStreamStatus'])
            ->name('api.v1.vision.stream.status');
        Route::get('/session/{session_id}/frames', [VisionController::class, 'getSessionFrames'])
            ->name('api.v1.vision.session.frames');
    });
});

/*
|--------------------------------------------------------------------------
| Cortex Neural Interface Routes
|--------------------------------------------------------------------------
*/
Route::prefix('cortex')->group(function () {
    Route::get('/agents/status', [CortexController::class, 'agentStatus'])->name('api.cortex.agents.status');
    Route::get('/system/stats', [CortexController::class, 'systemStats'])->name('api.cortex.system.stats');
    Route::get('/performance/history', [CortexController::class, 'performanceHistory'])->name('api.cortex.performance.history');
    Route::get('/tasks/live', [CortexController::class, 'liveTasks'])->name('api.cortex.tasks.live');
});
