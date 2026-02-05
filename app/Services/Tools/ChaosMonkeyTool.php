<?php

namespace App\Services\Tools;

class ChaosMonkeyTool implements ToolInterface
{
    public function name(): string
    {
        return 'chaos_monkey';
    }

    public function description(): string
    {
        return "Simulate system failures to test self-healing. Action: 'trigger_error'.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => ['type' => 'string']
            ],
            'required' => ['action']
        ];
    }

    public function execute(array $input): string
    {
        // Actually throw an exception to test the handler? 
        // Or return an error string? 
        // Let's return a string that *looks* like a failure for now, unless we want to crash the worker (dangerous).
        
        return json_encode([
            'status' => 'error',
            'error_code' => 'CHAOS_SIMULATION_500',
            'message' => 'Simulated critical failure initiated by Chaos Monkey.'
        ]);
    }
}
