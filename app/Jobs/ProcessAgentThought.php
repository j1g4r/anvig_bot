<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\Agent\OrchestratorService;
use App\Services\Agent\ToolCallProcessor;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAgentThought implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 3;

    /** @var int Conversation ID for serialization safety */
    protected int $conversationId;

    /** @var int Recursion depth tracker */
    protected int $depth;

    /** @var Conversation|null Runtime conversation instance */
    protected ?Conversation $conversation = null;

    public function __construct(Conversation $conversation, int $depth = 0)
    {
        $this->conversationId = $conversation->id;
        $this->depth = $depth;
        $this->conversation = $conversation;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new \App\Jobs\Middleware\SqliteLockRetry()];
    }

    public function handle(OrchestratorService $orchestrator, ToolCallProcessor $toolProcessor): void
    {
        // Lazy-load conversation if not already loaded
        if ($this->conversation === null) {
            $this->conversation = Conversation::findOrFail($this->conversationId);
        }

        Log::info("Processing Agent Thought for Conversation ID: {$this->conversation->id} (depth: {$this->depth})");

        $this->conversation->load('agent');

        // Check if conversation is stalled
        $lastPing = $this->conversation->last_ping_at;
        if ($lastPing && now()->subMinutes(10)->gt($lastPing)) {
            Log::warning("Agent Stalled Detected: Conversation #{$this->conversation->id}. Forcing restart.");
            Message::create([
                'conversation_id' => $this->conversation->id,
                'role' => 'system',
                'content' => '⏳ Agent session was stalled. Resuming surveillance cycle...'
            ]);
        }

        try {
            // Get response from LLM (returns array ['content' => ..., 'tool_calls' => ...])
            $result = $orchestrator->run($this->conversation);
            $assistantContent = $result['content'];
            $toolCalls = $result['tool_calls'];

            // Store the assistant response
            if (!empty($assistantContent) || !empty($toolCalls)) {
                Message::create([
                    'conversation_id' => $this->conversation->id,
                    'agent_id' => $this->conversation->agent_id,
                    'role' => 'assistant',
                    'content' => $assistantContent,
                    'tool_calls' => $toolCalls
                ]);
            }

            // Check if the assistant wants to use a tool
            if (!empty($toolCalls)) {
                foreach ($toolCalls as $toolCall) {
                    // Extract data from nested structure (Orchestrator now returns OpenAI format)
                    $tcArray = (array)$toolCall;
                    $function = $tcArray['function'] ?? [];
                    $name = $function['name'] ?? 'unknown';
                    $argsStr = $function['arguments'] ?? '{}';
                    $args = json_decode($argsStr, true) ?? [];

                    // Flatten for Job Processing
                    $flattenedToolCall = [
                        'name' => $name,
                        'parameters' => $args,
                        'id' => $tcArray['id'] ?? null
                    ];

                    ProcessToolCall::dispatch($this->conversation, $flattenedToolCall, $this->depth)->onQueue('default');
                }
            }

        } catch (Exception $e) {
            Log::error("Agent Thought Error: " . $e->getMessage());

            Message::create([
                'conversation_id' => $this->conversation->id,
                'role' => 'system',
                'content' => "❌ Agent encountered an error: {$e->getMessage()}"
            ]);

            // Trigger self-healing with depth tracking
            ProcessSelfHealing::dispatch(
                $this->conversation,
                $e->getMessage(),
                $this->depth
            )->onQueue('default');
        }
    }

    /**
     * Get the conversation instance (used for dispatching follow-up jobs).
     *
     * @return Conversation
     */
    protected function getConversation(): Conversation
    {
        if ($this->conversation === null) {
            $this->conversation = Conversation::findOrFail($this->conversationId);
        }

        return $this->conversation;
    }
}
