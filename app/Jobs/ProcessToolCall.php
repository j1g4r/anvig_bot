<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\ToolRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessToolCall implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 3;

    public function __construct(
        public Conversation $conversation,
        public array $toolCall,
        public int $depth = 0
    ) {}

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new \App\Jobs\Middleware\SqliteLockRetry()];
    }

    public function handle(ToolRegistry $toolRegistry): void
    {
        $toolName = $this->toolCall['name'] ?? 'unknown';
        $parameters = $this->toolCall['parameters'] ?? [];

        Log::info("Processing Tool Call: {$toolName}", ['parameters' => $parameters]);

        $monitoring = new \App\Services\MonitoringService();
        $trace = $monitoring->startTrace(
            $this->conversation->id,
            $this->conversation->agent_id ?? 1,
            'System', // Or Agent Name if available
            $toolName,
            $parameters
        );

        try {
            // Execute the tool
            $result = $toolRegistry->execute($toolName, $parameters, $this->conversation);

            // Store tool result as a system message
            Message::create([
                'conversation_id' => $this->conversation->id,
                'role' => 'system',
                'content' => "Tool '{$toolName}' result:\n" . (is_string($result) ? $result : json_encode($result, JSON_PRETTY_PRINT))
            ]);
            
            // End Trace Success
            $monitoring->endTrace($trace, $result, 'success');

            // Continue the agent loop with fresh thought
            ProcessAgentThought::dispatch($this->conversation, $this->depth)->onQueue('default');

        } catch (Throwable $e) {
            Log::error("Tool Call Error ({$toolName}): " . $e->getMessage());

            // End Trace Error
            $monitoring->endTrace($trace, "Error: " . $e->getMessage(), 'error');

            // Store failure context
            Message::create([
                'conversation_id' => $this->conversation->id,
                'role' => 'system',
                'content' => "❌ Tool '{$toolName}' failed: {$e->getMessage()}"
            ]);

            // Trigger self-healing with depth tracking to prevent infinite loops
            ProcessSelfHealing::dispatch(
                $this->conversation,
                $e->getMessage(),
                $this->depth
            )->onQueue('default');
        }
    }
}
