<?php

namespace App\Services\Agent;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\AI\AIService;
use App\Services\AI\ModelRouterService;
use App\Services\ContextService;
use App\Services\ContinuousLearningService;
use App\Services\InferenceCacheService;
use App\Services\LocalizationService;
use Illuminate\Support\Facades\Log;

class OrchestratorService
{
    protected array $tools = [];

    public function __construct()
    {
        // Tools are registered in ToolRegistry or similar, but for now filtering is done here or in AIService.
        // We need to know available tools to pass to LLM.
        // Assuming ToolRegistry exists or we use a fresh instance.
        // For now, let's assume AIService handles tool definition if passed, or we assume a global registry.
        // The original AgentService instantiated tools in constructor.
    }

    /**
     * Run the agent cycle.
     * Returns an array: ['content' => string, 'tool_calls' => array|null]
     */
    public function run(Conversation $conversation): array
    {
        // 1. Auto-Compression
        (new ContextService())->compress($conversation);
        $conversation->refresh();

        // 2. Build Context
        $context = $this->buildContext($conversation);

        // 3. Prepare Tools
        $availableTools = (new \App\Services\ToolRegistry())->getToolsForAgent($conversation->agent);

        // 4. Inference Cache
        $lastUserMsg = $conversation->messages()->where('role', 'user')->latest()->first();
        if ($lastUserMsg && empty($lastUserMsg->images)) {
             $cacheHit = (new InferenceCacheService())->lookup($lastUserMsg->content);
             if ($cacheHit) {
                 Log::info("⚡️ Zero-Latency Cache Hit");
                 return ['content' => $cacheHit->response, 'tool_calls' => null];
             }
        }

        // 5. Model Routing
        $model = (new ModelRouterService())->selectModel($context);

        // 6. Call AI
        $params = [
            'model' => $model,
            'messages' => $context,
        ];
        if (!empty($availableTools)) {
            $params['tools'] = $availableTools;
        }

        try {
            $response = (new AIService())->chat($params, [
                'conversation_id' => $conversation->id, 
                'agent_id' => $conversation->agent_id
            ]);

            $choice = $response->choices[0];
            $content = $choice->message->content ?? '';
            $toolCalls = $choice->message->toolCalls ?? null;

            if ($toolCalls) {
                Log::debug("Orchestrator Raw ToolCalls:", ['data' => array_map(fn($t) => (array)$t, $toolCalls)]);
            }

            // Cache if applicable
            if ($lastUserMsg && empty($toolCalls) && !empty($content)) {
                (new InferenceCacheService())->store($lastUserMsg->content, $content);
            }

            return [
                'content' => $content, 
                'tool_calls' => $toolCalls ? array_map(function($tc) {
                    // Scenario A: SDK Object
                    if (is_object($tc)) {
                        return [
                            'id' => $tc->id ?? null,
                            'type' => $tc->type ?? 'function',
                            'function' => [
                                'name' => $tc->function->name ?? 'unknown',
                                'arguments' => $tc->function->arguments ?? '{}' // Keep as JSON string for API history
                            ]
                        ];
                    }
                    // Scenario B: Array (Ensure correct nested structure)
                    if (is_array($tc)) {
                         if (isset($tc['function'])) {
                             // Already nested? ensure arguments is string
                             $args = is_array($tc['function']) ? ($tc['function']['arguments'] ?? '{}') : ($tc['function']->arguments ?? '{}');
                             if (is_array($args)) $args = json_encode($args);
                             
                             return [
                                'id' => $tc['id'] ?? null,
                                'type' => $tc['type'] ?? 'function',
                                'function' => [
                                    'name' => is_array($tc['function']) ? ($tc['function']['name'] ?? 'unknown') : ($tc['function']->name ?? 'unknown'),
                                    'arguments' => $args
                                ]
                             ];
                         }
                         // If it was the flat structure (legacy/cache), remap it to nested
                         return [
                            'id' => $tc['id'] ?? null,
                            'type' => $tc['type'] ?? 'function',
                            'function' => [
                                'name' => $tc['name'] ?? 'unknown',
                                'arguments' => json_encode($tc['parameters'] ?? [])
                            ]
                         ];
                    }
                    return (array)$tc;
                }, $toolCalls) : null
            ];

            // 7. MONITORING HOOK: Capture Thought Process
            if (preg_match('/<THOUGHT>\s*(.*?)\s*<\/THOUGHT>/is', $content, $matches)) {
                $thoughtContent = $matches[1];
                $monitoring = new \App\Services\MonitoringService();
                
                // Extract usage info (if available via some metadata passed back)
                // For now, simple trace
                $trace = $monitoring->startTrace(
                    $conversation->id,
                    $conversation->agent_id,
                    $conversation->agent->name ?? 'Unknown Agent',
                    'cognitive_process', 
                    ['thought' => $thoughtContent]
                );
                
                $monitoring->endTrace($trace, ['status' => 'completed'], 'success');
            }

        } catch (\Exception $e) {
            Log::error("Orchestrator AI Error: " . $e->getMessage());
            throw $e;
        }
    }

    protected function buildContext(Conversation $conversation): array
    {
        // Load messages (Limit to last 15)
        $messages = $conversation->messages()
            ->latest('id')
            ->take(15)
            ->get()
            ->sortBy('id');
        
        $context = [];

        // Summary
        if ($conversation->summary) {
            $context[] = ['role' => 'system', 'content' => "PREVIOUS SUMMARY: " . $conversation->summary];
        }

        // Learning
        $adaptedPrompt = (new ContinuousLearningService())->getAdaptedSystemPrompt($conversation->agent);
        $context[] = ['role' => 'system', 'content' => $adaptedPrompt];

        // Localization
        $context[] = ['role' => 'system', 'content' => (new LocalizationService())->getLocalContext()];

        // Team Context
        if ($conversation->participants->count() > 1) {
            $participants = $conversation->participants->map(fn($p) => $p->agent->name)->join(', ');
            $context[] = [
                'role' => 'system',
                'content' => "TEAM MODE: [$participants]. Use @AgentName to speak to them."
            ];
        }

        // Personality
        if ($conversation->agent->personality) {
            $context[] = ['role' => 'system', 'content' => "PERSONALITY: " . $conversation->agent->personality];
        }

        // DRIFT PREVENTION: Inject Active Kanban Task
        $activeTask = \App\Models\KanbanTask::where('agent_id', $conversation->agent_id)
            ->where('status', 'in_progress')
            ->first();

        if ($activeTask) {
            $context[] = [
                'role' => 'system', 
                'content' => "🚨 CURRENT MISSION 🚨\nYou are currently assigned to task #{$activeTask->id}: \"{$activeTask->title}\".\nDescription: {$activeTask->description}\n\nGUIDELINES:\n1. Focus ONLY on completing this task.\n2. Do NOT start new unrelated topics.\n3. If providing code, ensure it solves THIS problem.\n4. When finished, use 'kanban_board' tool to mark it as 'done'."
            ];
        }

        // Messages
        foreach ($messages as $msg) {
            $content = $msg->content;
            // Handle Images... (Simplified for now)
             if (!empty($msg->images)) {
                $contentPayload = [];
                if (!empty($msg->content)) $contentPayload[] = ['type' => 'text', 'text' => $msg->content];
                foreach ($msg->images as $img) {
                    // Logic to load image
                    $contentPayload[] = ['type' => 'image_url', 'image_url' => ['url' => '...']]; // Fix actual path logic if needed
                }
                $content = $contentPayload;
            }

            $payload = ['role' => $msg->role, 'content' => $content];
            if ($msg->tool_calls) $payload['tool_calls'] = $msg->tool_calls;
            if ($msg->tool_call_id) $payload['tool_call_id'] = $msg->tool_call_id;
            
            // Name injection logic...
            
            $context[] = $payload;
        }

        return $context;
    }
}
