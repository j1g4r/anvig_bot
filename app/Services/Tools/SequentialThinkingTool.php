<?php

namespace App\Services\Tools;

class SequentialThinkingTool implements ToolInterface
{
    public function name(): string
    {
        return 'sequential_thinking';
    }

    public function description(): string
    {
        return "A tool for deep reasoning and step-by-step planning. Use this BEFORE taking complex actions. Input: 'thought' (string) and 'step' (int).";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'thought' => ['type' => 'string'],
                'step' => ['type' => 'integer'],
                'next_step_needed' => ['type' => 'boolean']
            ],
            'required' => ['thought', 'step']
        ];
    }

    public function execute(array $input): string
    {
        // Robustness: Unwrap 'params' if the LLM hallucinated the Action/Params pattern
        $args = array_merge($input, $input['params'] ?? []);

        $thought = $args['thought'] ?? 'No thought provided';
        $step = $args['step'] ?? 1;
        $totalSteps = $args['total_steps'] ?? 5;
        $nextStepNeeded = $args['next_step_needed'] ?? true;

        // The value of this tool is primarily in the *Trace* it leaves in the system logs
        $result = [
            'status' => 'thought_recorded',
            'step' => $step,
            'progress' => "{$step}/{$totalSteps}",
            'thought_preview' => substr($thought, 0, 100) . '...',
            'advice' => $nextStepNeeded ? 'Continue thinking...' : 'Ready to execute.'
        ];

        return json_encode($result);
    }
}
