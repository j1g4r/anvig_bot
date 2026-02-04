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
        $holdTasks = KanbanTask::where('status', 'hold')->get();
        if ($holdTasks->isEmpty()) {
            return;
        }

        $agents = Agent::all();
        // Assuming Jerry is ID 1, or we just pick a "Manager"
        // For simplicity, we'll assign based on keywords or random if no match
        // In a real LLM scenario, we'd feed the task title to the LLM to pick the best agent.

        foreach ($holdTasks as $task) {
            $this->processTask($task, $agents);
        }
    }

    private function processTask(KanbanTask $task, $agents)
    {
        // Simple heuristic for demo purposes (Role Simulation)
        $title = strtolower($task->title);
        
        $assignedAgentId = null;
        
        // Logic mapping (Keyword -> Role/Agent)
        // Adjust these keywords based on your actual agents
        if (str_contains($title, 'vision') || str_contains($title, 'design') || str_contains($title, 'mobile')) {
            $assignedAgentId = 2; // e.g., Frontend/Designer Agent
        } elseif (str_contains($title, 'test') || str_contains($title, 'protocol')) {
            $assignedAgentId = 3; // e.g., QA/Safety Agent
        } elseif (str_contains($title, 'plan') || str_contains($title, 'manage')) {
            $assignedAgentId = 1; // Jerry (Manager)
        }
        
        // Fallback to random if no keyword match
        if (!$assignedAgentId && $agents->isNotEmpty()) {
            $assignedAgentId = $agents->random()->id;
        }

        if ($assignedAgentId) {
            $task->update([
                'agent_id' => $assignedAgentId,
                'status' => 'todo', // Move to Ready
                'priority' => 'high', // Bump priority
            ]);
            
            $agentName = $agents->find($assignedAgentId)?->name ?? 'Unknown';
            Log::info("Jerry assigned task '{$task->title}' to Agent {$agentName}");
            
            // Trigger the agent to start working
            ProcessKanbanTask::dispatch($task);
        }
    }
}
