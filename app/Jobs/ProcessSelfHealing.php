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

    /** Maximum allowed self-healing depth per conversation */
    public const MAX_DEPTH = 3;

    public function __construct(
        protected Conversation $conversation,
        protected string $errorMessage,
        protected int $depth = 0
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

    public function handle(AgentService $agentService): void
    {
        // RECURSION GUARD: Prevent infinite self-healing loops
        if ($this->depth >= self::MAX_DEPTH) {
            Message::create([
                'conversation_id' => $this->conversation->id,
                'role' => 'system',
                'content' => "🚫 SELF-HEALING DEPTH LIMIT REACHED ({$this->depth}/" . self::MAX_DEPTH . ")\n" .
                    "Original error: \"{$this->errorMessage}\"\n" .
                    "Multiple automated fix attempts failed. Human intervention required."
            ]);
            return;
        }

        // ⚠️ AUTONOMOUS DEBUGGER PROTOCOL
        // 1. Search for past solutions in AI_INDEX.md (Simulated here)
        $simulatedMemory = "Checking AI_INDEX... No exact match found.";
        if (str_contains($this->errorMessage, 'Payload too large')) {
             $simulatedMemory = "Checking AI_INDEX... MATCH FOUND: 'MonitoringService payload exceeds 10KB'. \nSUGGESTED ACTION: Truncate output in app/Services/MonitoringService.php.";
        }
        if (str_contains($this->errorMessage, 'syntax error')) {
             $simulatedMemory = "Checking AI_INDEX... MATCH FOUND: 'PHP Syntax Error'. \nSUGGESTED ACTION: Use `code_analyzer` tool with action `check_syntax` on the file.";
        }

        // Inject a system message to trigger the self-healing thought process
        Message::create([
            'conversation_id' => $this->conversation->id,
            'role' => 'system',
            'content' => "⚠️ AUTONOMOUS SELF-HEALING SYSTEM ENGAGED (Attempt {$this->depth}/" . self::MAX_DEPTH . ")\n" .
                "The previous tool executed by this agent failed.\n\n" .
                "**ERROR:** \"{$this->errorMessage}\"\n" .
                "**MEMORY:** {$simulatedMemory}\n\n" .
                "**REQUIRED ACTIONS:**\n" .
                "1. **ANALYZE:** Do not guess. Use `system_debugger`, `read_file`, or `code_analyzer` to find the root cause.\n" .
                "2. **FIX:** Apply the correction (e.g. edit file, fix config, run command).\n" .
                "3. **VERIFY:** Run `check_syntax` or a test command to prove the fix works.\n" .
                "4. **RETRY:** Only if verified, re-state the original intent.\n\n" .
                "FAILURE TO FIX will result in escalation. You have permission to edit code."
        ]);

        // Run the agent with context
        $agentService->run($this->conversation, $this->depth + 1);
    }
}
