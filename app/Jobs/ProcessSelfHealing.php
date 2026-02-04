<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\AgentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSelfHealing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Conversation $conversation,
        protected string $errorMessage
    ) {}

    public function handle(AgentService $agentService): void
    {
        // Inject a system message to trigger the self-healing thought process
        Message::create([
            'conversation_id' => $this->conversation->id,
            'role' => 'system',
            'content' => "⚠️ AUTONOMOUS DEBUGGER TRIGGERED\n" .
                "The previous tool call failed with the following error: \"{$this->errorMessage}\".\n" .
                "Your task is to:\n" .
                "1. Use 'system_debugger' to inspect the cause (logs, permissions, path).\n" .
                "2. Formulate a fix (e.g. fix command typo, create missing directory).\n" .
                "3. Execute the fix and retry the original intent.\n" .
                "DO NOT ask the user for help yet. Try to fix it yourself first."
        ]);

        // Run the agent with context
        $agentService->run($this->conversation);
    }
}
