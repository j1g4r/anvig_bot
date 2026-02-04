<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class ModelRouterService
{
    /**
     * Determine the best model for the given messages.
     */
    public function selectModel(array $messages): string
    {
        $complexity = $this->analyzeComplexity($messages);
        
        $fastModel = env('MODEL_FAST', 'llama3.2');
        $smartModel = env('MODEL_SMART', 'gpt-4');

        if ($complexity === 'high') {
            Log::info("ModelRouter: High complexity detected. Routing to SMART model: $smartModel");
            return $smartModel;
        }

        Log::info("ModelRouter: Standard task. Routing to FAST model: $fastModel");
        return $fastModel;
    }

    /**
     * Heuristic analysis of complexity.
     */
    protected function analyzeComplexity(array $messages): string
    {
        // Get the last user message
        $lastMessage = '';
        foreach (array_reverse($messages) as $msg) {
            if ($msg['role'] === 'user') {
                $lastMessage = $msg['content'];
                break;
            }
        }

        if (empty($lastMessage)) return 'low';

        // 1. Length Check
        if (strlen($lastMessage) > 500) {
            return 'high';
        }

        // 2. Keyword Check
        $complexKeywords = [
            'code', 'function', 'class', 'debug', 'fix', 'error', 
            'analyze', 'plan', 'strategy', 'legal', 'audit',
            'write', 'generate', 'refactor', 'optimize'
        ];

        foreach ($complexKeywords as $keyword) {
            if (stripos($lastMessage, $keyword) !== false) {
                return 'high';
            }
        }

        // 3. Chain of Thought depth (if user asks for thinking)
        if (stripos($lastMessage, 'think') !== false || stripos($lastMessage, 'reason') !== false) {
            return 'high';
        }

        return 'low';
    }
}
