<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\MemoryAnalysisController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

// Auth bypassed group
Route::prefix('')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/notifications', [ProfileController::class, 'updateNotifications'])->name('notifications.update');

    Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');
    Route::get('/agents/vault', [AgentController::class, 'memories'])->name('agents.vault');
    Route::get('/cortex', [AgentController::class, 'cortex'])->name('cortex.index');
    Route::get('/agents/{agent}', [AgentController::class, 'show'])->name('agents.show');
    
    // God Mode
    Route::post('/god-mode/toggle', [\App\Http\Controllers\GodModeController::class, 'toggle'])->name('god_mode.toggle');
    Route::patch('/agents/{agent}', [AgentController::class, 'update'])->name('agents.update');
    Route::get('/agents/{agent}/chat/{conversation}/export', [AgentController::class, 'export'])->name('agents.export');
    Route::post('/agents/{agent}/chat/{conversation}', [AgentController::class, 'chat'])->name('agents.chat');
    Route::post('/conversation/{conversation}/participants', [AgentController::class, 'addParticipant'])->name('conversation.participants.add');
    Route::delete('/conversation/{conversation}/participants', [AgentController::class, 'removeParticipant'])->name('conversation.participants.remove');

    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring');
    Route::get('/memory/galaxy', [MemoryAnalysisController::class, 'index'])->name('memory.galaxy');

    Route::prefix('kanban')->name('kanban.')->group(function () {
        Route::get('/', [KanbanController::class, 'index'])->name('index');
        Route::post('/', [KanbanController::class, 'store'])->name('store');
        Route::patch('/{task}', [KanbanController::class, 'update'])->name('update');
        Route::delete('/{task}', [KanbanController::class, 'destroy'])->name('destroy');
        Route::post('/{task}/run', [KanbanController::class, 'run'])->name('run');
    });

    Route::prefix('mfa')->name('mfa.')->group(function () {
        Route::get('/', [\App\Http\Controllers\MfaController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\MfaController::class, 'store'])->name('store');
        Route::post('/generate', [\App\Http\Controllers\MfaController::class, 'generate'])->name('generate');
        Route::delete('/', [\App\Http\Controllers\MfaController::class, 'destroy'])->name('destroy');
    });

    // Analytics
    Route::get('/analytics', [\App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/analytics/stats', [\App\Http\Controllers\AnalyticsController::class, 'stats'])->name('analytics.stats');

    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [\App\Http\Controllers\DocumentController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\DocumentController::class, 'store'])->name('store');
        Route::delete('/{document}', [\App\Http\Controllers\DocumentController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('workflows')->name('workflows.')->group(function () {
        Route::get('/', [\App\Http\Controllers\WorkflowController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\WorkflowController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\WorkflowController::class, 'store'])->name('store');
        Route::get('/{workflow}/edit', [\App\Http\Controllers\WorkflowController::class, 'edit'])->name('edit');
        Route::put('/{workflow}', [\App\Http\Controllers\WorkflowController::class, 'update'])->name('update');
        Route::post('/{workflow}/run', [\App\Http\Controllers\WorkflowController::class, 'run'])->name('run');
        Route::delete('/{workflow}', [\App\Http\Controllers\WorkflowController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('smart-home')->name('smart-home.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SmartHomeController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\SmartHomeController::class, 'store'])->name('store');
        Route::post('/{device}/control', [\App\Http\Controllers\SmartHomeController::class, 'control'])->name('control');
        Route::delete('/{device}', [\App\Http\Controllers\SmartHomeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SettingsController::class, 'index'])->name('index');
        Route::post('/nav-visibility', [\App\Http\Controllers\SettingsController::class, 'updateNavVisibility'])->name('nav-visibility');
    });

    Route::prefix('ollama')->name('ollama.')->group(function () {
        Route::get('/', [\App\Http\Controllers\OllamaClusterController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\OllamaClusterController::class, 'store'])->name('store');
        Route::patch('/{node}', [\App\Http\Controllers\OllamaClusterController::class, 'update'])->name('update');
        Route::delete('/{node}', [\App\Http\Controllers\OllamaClusterController::class, 'destroy'])->name('destroy');
        Route::post('/{node}/health', [\App\Http\Controllers\OllamaClusterController::class, 'healthCheck'])->name('health');
        Route::post('/health-all', [\App\Http\Controllers\OllamaClusterController::class, 'healthCheckAll'])->name('health.all');
        Route::post('/{node}/pull', [\App\Http\Controllers\OllamaClusterController::class, 'pullModel'])->name('pull');
    });
    Route::prefix('voice')->name('voice.')->group(function () {
        Route::post('/transcribe', [\App\Http\Controllers\VoiceController::class, 'transcribe'])->name('transcribe');
        Route::post('/speak', [\App\Http\Controllers\VoiceController::class, 'speak'])->name('speak');
    });
});
// require __DIR__.'/auth.php';
