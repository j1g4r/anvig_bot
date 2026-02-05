<?php

namespace App\Services;

use App\Models\KanbanTask;
use App\Models\Agent;
use App\Jobs\ProcessKanbanTask;
use Illuminate\Support\Facades\Log;

class TaskTriageService
{
    /**
     * Jerry (Agent 1) decides what to do with 'hold' tasks.
     */
    public function triage(): void
    {
        // 1. Sort backlog by Priority and Age(FIFO)
        // High priority first, then older tasks first.
        $holdTasks = KanbanTask::whereIn('status', ['hold', 'todo'])
            ->orderByRaw("CASE WHEN priority = 'high' THEN 1 WHEN priority = 'medium' THEN 2 ELSE 3 END")
            ->orderBy('created_at', 'asc')
            ->get();

        if ($holdTasks->isEmpty()) {
            return;
        }

        $agents = Agent::all();

        foreach ($holdTasks as $task) {
            $this->processTask($task, $agents);
        }
    }

    private function processTask(KanbanTask $task, $agents)
    {
        // 1. Determine the Best Candidate
        $title = strtolower($task->title);
        $assignedAgentId = null;

        // Specialized Routing
        if (str_contains($title, 'vision') || str_contains($title, 'design') || str_contains($title, 'frontend') || str_contains($title, 'ui')) {
            $assignedAgentId = Agent::where('name', 'Developer')->first()?->id ?? 2;
        } elseif (str_contains($title, 'research') || str_contains($title, 'analyze') || str_contains($title, 'find')) {
            $assignedAgentId = Agent::where('name', 'Researcher')->first()?->id;
        } elseif (str_contains($title, 'audit') || str_contains($title, 'security')) {
            $assignedAgentId = Agent::where('name', 'Auditor')->first()?->id;
        } else {
            // Default to Jerry or Developer for generic tasks
            $assignedAgentId = Agent::where('name', 'Jerry')->first()?->id ?? 1;
        }

        // 2. BUSY CHECK (Sequential Execution Enforcement)
        // If the candidate is already working on something, DO NOT assign.
        // Waiting tasks will stay in 'hold' until the next triage cycle (triggered by task completion).
        if ($assignedAgentId) {
            $activeTaskCount = KanbanTask::where('agent_id', $assignedAgentId)
                ->whereIn('status', ['todo', 'in_progress'])
                ->count();

            if ($activeTaskCount > 0) {
                // Agent is busy. Skip this task for now.
                Log::info("Skipping assignment of '{$task->title}' to Agent ID {$assignedAgentId}. Agent is busy with {$activeTaskCount} tasks.");
                return;
            }

            // Agent is Free -> Assign
            $task->update([
                'agent_id' => $assignedAgentId,
                'status' => 'in_progress', // Move directly to in_progress to block other assignments immediately
                'priority' => $task->priority ?? 'medium', 
            ]);
            
            $agentName = $agents->find($assignedAgentId)?->name ?? 'Unknown';
            Log::info("✅ Jerry dispatched task '{$task->title}' to Agent {$agentName}");
            
            // Trigger the job
            ProcessKanbanTask::dispatch($task);
        }
    }
}
