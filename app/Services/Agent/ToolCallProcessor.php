<?php

namespace App\Services\Agent;

class ToolCallProcessor
{
    /**
     * Extract tool calls from the response.
     * In this new architecture, Orchestrator returns them explicitly.
     * This method helps normalize them if they come from text parsing (legacy) or just pass through.
     */
    public function extractToolCalls(array|string $response): ?array
    {
        if (is_array($response) && isset($response['tool_calls'])) {
            return $response['tool_calls'];
        }

        // Fallback: If response is string, try to parse XML <tool_code>?
        // For now, assuming Native OpenAI calls.
        return null;
    }
}
