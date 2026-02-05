<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\KanbanTask;

class SelfHealingService
{
    /**
     * Scan failed jobs and apply heuristics.
     */
    public function heal()
    {
        $failedJobs = DB::table('failed_jobs')->limit(50)->get();

        if ($failedJobs->isEmpty()) {
            return;
        }

        foreach ($failedJobs as $job) {
            $this->diagnoseAndTreat($job);
        }
    }

    private function diagnoseAndTreat($job)
    {
        $payload = json_decode($job->payload, true);
        $exception = $job->exception;
        
        // --- Strategy 1: Transient Errors (Auto-Retry) ---
        if (str_contains($exception, 'ProcessTimedOutException') || 
            str_contains($exception, 'LockTimeoutException') ||
            str_contains($exception, 'Deadlock found') ||
            str_contains($exception, 'Connection timed out')) {
            
            Log::info("SelfHealer: Retrying transient error for Job {$job->uuid}");
            Artisan::call('queue:retry', ['id' => $job->uuid]);
            return;
        }

        // --- Strategy 2: Fatal Code Errors (Dispatch Engineer) ---
        if (str_contains($exception, 'ParseError') || 
            str_contains($exception, 'syntax error') || 
            str_contains($exception, 'Undefined variable') ||
            str_contains($exception, 'Call to undefined method')) {
            
            $this->dispatchFixTask($job, "Critical Code Error");
            return;
        }

        // --- Strategy 3: Agent Hallucinations / Logic Errors ---
        // e.g. "Specialist agent '' not found"
        if (str_contains($exception, 'Specialist agent') || 
            str_contains($exception, 'Invalid action')) {
            
            // These likely won't be fixed by retrying code, but the agent needs coaching.
            // For now, track it so we don't ignore it.
            $this->dispatchFixTask($job, "Agent Logic/Hallucination Error");
            return;
        }

        // Default: Ignore or log
        // We might choose to retry once just in case? 
        // For now, leave strictly broken stuff alone to clean up manualy if it doesn't match patterns.
    }

    private function dispatchFixTask($job, $type)
    {
        // Extract a readable error snippet
        $lines = explode("\n", $job->exception);
        $errorSummary = $lines[0] ?? 'Unknown Error';
        $location = $lines[1] ?? 'Unknown Location';

        $title = "🚨 FIX: $type in Queue";
        
        // Prevent Spam: Check if we already have an active task for this error type/job
        // We accept that duplicates might occur if the error is generic, but we try to unique by exception summary
        $exists = KanbanTask::where('title', $title)
            ->where('description', 'like', "%$errorSummary%")
            ->where('status', '!=', 'done')
            ->exists();

        if ($exists) {
            return; // Already being worked on
        }

        // Create Task for Developer Agent
        // We default to 'todo' or 'hold'. The TriageService will pick it up or we assign 'Developer' directly.
        KanbanTask::create([
            'title' => $title,
            'description' => "Automated Bug Report from Self-Healer.\n\nError: $errorSummary\nLocation: $location\nUUID: {$job->uuid}\n\nAction: Investigate code, fix bug, then retry job.",
            'status' => 'todo',
            'priority' => 'high',
            'agent_id' => null, // Let Triage assign to Developer
            'tags' => ['msg:self_healing', 'bug']
        ]);

        Log::info("SelfHealer: Created Kanban task for fatal error: $errorSummary");
        
        // Mark job as 'acknowledged' (optional, maybe keep it in failed_jobs until fixed)
    }
}
