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
    public function startTrace(int $conversationId, int $agentId, string $toolName, array $input): Trace
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
            $toolName,
            $input
        ));

        return $trace;
    }

    /**
     * Complete an existing trace.
     */
    public function endTrace(Trace $trace, $output, string $status = 'success'): void
    {
        $duration = (int) (microtime(true) * 1000) - (int) ($trace->created_at->getTimestamp() * 1000);
        
        $trace->update([
            'output' => is_array($output) ? $output : ['raw' => $output],
            'duration_ms' => $duration,
            'status' => $status,
        ]);

        broadcast(new ToolExecuted(
            $trace->id,
            $output,
            $duration,
            $status
        ));
    }
}
