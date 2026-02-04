<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\ScheduledTask;
use App\Services\AgentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessScheduledMission implements ShouldQueue
{
    use Queueable;

    public $taskId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $taskId)
    {
        $this->taskId = $taskId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $task = ScheduledTask::find($this->taskId);
        if (!$task || $task->status !== 'pending') return;

        try {
            $task->update(['status' => 'running']);

            // 1. Inject the mission as a System message explaining the scheduled task
            Message::create([
                'conversation_id' => $task->conversation_id,
                'role' => 'system',
                'content' => "AUTONOMOUS MISSION TRIGGERED: {$task->mission_prompt}",
            ]);

            // 2. Run the agent loop
            $agentService = new AgentService();
            $agentService->run($task->conversation);

            $task->update(['status' => 'completed']);

        } catch (\Exception $e) {
            Log::error("ProcessScheduledMission Error: " . $e->getMessage());
            $task->update([
                'status' => 'failed',
                'result' => $e->getMessage()
            ]);
        }
    }
}
