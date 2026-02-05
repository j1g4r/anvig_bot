<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Jobs\ProcessAgentThought;
use App\Jobs\ProcessToolCall;
use App\Jobs\ProcessSelfHealing;
use Illuminate\Support\Facades\Log;
use Throwable;

class AgentService
{
    public function run(Conversation $conversation, int $depth = 0): void
    {
        // Log the depth for tracking in case of nested failures
        Log::info("AgentService::run depth={$depth}", [
            'conversation_id' => $conversation->id,
            'depth' => $depth,
        ]);

        ProcessAgentThought::dispatch($conversation, $depth)->onQueue('default');
    }

    public function processToolCall(Conversation $conversation, array $toolCall, int $depth = 0): void
    {
        ProcessToolCall::dispatch($conversation, $toolCall, $depth)->onQueue('default');
    }

    public function handleToolCallError(Conversation $conversation, Throwable $error, int $depth = 0): void
    {
        // Trigger self-healing with depth tracking
        ProcessSelfHealing::dispatch(
            $conversation,
            $error->getMessage(),
            $depth
        )->onQueue('default');
    }
}
