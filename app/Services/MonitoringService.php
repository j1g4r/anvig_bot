<?php

namespace App\Services;

use App\Models\Trace;
use App\Events\ToolExecuting;
use App\Events\ToolExecuted;

class MonitoringService
{
    /**
     * Start a new tool trace.
     */
    public function startTrace(int $conversationId, int $agentId, string $agentName, string $toolName, array $input): Trace
    {
        $trace = Trace::create([
            'conversation_id' => $conversationId,
            'agent_id' => $agentId,
            'tool_name' => $toolName,
            'input' => $input,
            'status' => 'executing',
        ]);

        broadcast(new ToolExecuting(
            $trace->id,
            $conversationId,
            $agentId,
            $agentName,
            $toolName,
            $input
        ));

        return $trace;
    }

    /**
     * Complete an existing trace.
     */
    public function endTrace(Trace $trace, $output, string $status = 'success', array $usage = []): void
    {
        $duration = (int) (microtime(true) * 1000) - (int) ($trace->created_at->getTimestamp() * 1000);
        
        $updateData = [
            'output' => is_array($output) ? $output : ['raw' => $output],
            'duration_ms' => $duration,
            'status' => $status,
        ];

        if (!empty($usage)) {
            $updateData['tokens_input'] = $usage['prompt_tokens'] ?? null;
            $updateData['tokens_output'] = $usage['completion_tokens'] ?? null;
            $updateData['tokens_total'] = $usage['total_tokens'] ?? null;
        }

        $trace->update($updateData);

        broadcast(new ToolExecuted(
            $trace->id,
            $output,
            $duration,
            $status,
            $usage // Pass usage to frontend event if needed later
        ));
    }
}
