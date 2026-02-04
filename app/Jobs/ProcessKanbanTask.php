<?php

namespace App\Jobs;

use App\Models\Agent;
use App\Models\Conversation;
use App\Models\KanbanTask;
use App\Models\Message;
use App\Services\AgentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessKanbanTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected KanbanTask $task
    ) {}

    public function handle(AgentService $agentService): void
    {
        if (!$this->task->agent_id) {
            return;
        }

        \Illuminate\Support\Facades\Log::info("🚀 ProcessKanbanTask Started: " . $this->task->title);

        $agent = Agent::find($this->task->agent_id);
        
        // 1. Create a new conversation for this task
        $conversation = Conversation::create([
            'agent_id' => $agent->id,
            'title' => "Task: " . $this->task->title,
        ]);

        // 2. Add the mission statement
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => "MISSION ASSIGNED from Kanban Board:\n" .
                "Title: {$this->task->title}\n" .
                "Description: {$this->task->description}\n" .
                "Priority: {$this->task->priority}\n\n" .
                "Please begin working on this task immediately. Status has been moved to 'in_progress'."
        ]);

        // 3. Update task status
        $this->task->update(['status' => 'in_progress']);

        // 4. Run the agent
        $agentService->run($conversation);
    }
}
