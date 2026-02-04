<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\Tools\ShellTool;
use App\Services\Tools\ToolInterface;
use App\Services\Tools\ContextAwareToolInterface;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class AgentService
{
    protected array $tools = [];

    public function __construct()
    {
        // Register default tools
        $this->registerTool(new ShellTool());
        $this->registerTool(new Tools\FileTool());
        $this->registerTool(new Tools\BrowserTool());
        $this->registerTool(new Tools\MemoryTool());
        $this->registerTool(new Tools\ResearchTool());
        $this->registerTool(new Tools\DatabaseTool());
        $this->registerTool(new Tools\DelegateTool());
        $this->registerTool(new Tools\WebhookTool());
        $this->registerTool(new Tools\CanvasTool());
        $this->registerTool(new Tools\ScheduleTool());
        $this->registerTool(new Tools\DesktopTool());
        $this->registerTool(new Tools\GraphTool(new KnowledgeService()));
        $this->registerTool(new Tools\DebuggerTool());
        $this->registerTool(new Tools\KanbanTool());
        $this->registerTool(new Tools\ComplianceTool());
        $this->registerTool(new Tools\MfaTool());
        $this->registerTool(new Tools\RagTool());
        $this->registerTool(new Tools\SmartHomeTool());
        $this->registerTool(new Tools\LearningTool());
        $this->registerTool(new Tools\NotifyTool());
        $this->registerTool(new Tools\RoadmapTool());

        // Discover and Register Plugin Tools
        $pluginManager = new \App\Services\Plugin\PluginManager();
        foreach ($pluginManager->getAllTools() as $tool) {
            $this->registerTool($tool);
        }
    }

    public function registerTool(ToolInterface $tool)
    {
        $this->tools[$tool->name()] = $tool;
    }

    public function run(Conversation $conversation, int $steps = 5)
    {
        if ($steps <= 0) return;

        // Set context for tools (like CanvasTool, ScheduleTool)
        app()->instance('active_conversation_id', $conversation->id);
        app()->instance('active_agent_id', $conversation->agent_id);

        // 1. Auto-Compression Trigger
        $contextService = new ContextService();
        $contextService->compress($conversation);

        // Refresh conversation to pick up any changes (like agent_id or summary) from previous steps or compression
        $conversation->refresh();

        // Load messages
        $messages = $conversation->messages()->orderBy('id')->get();
        
        $context = [];
        // 2. Add Summary as the lead context if it exists
        if ($conversation->summary) {
            $context[] = [
                'role' => 'system', 
                'content' => "PREVIOUS CONVERSATION SUMMARY: " . $conversation->summary
            ];
        }


        // 3. Add Agent System Prompt with Learned Adaptations
        $learningService = new ContinuousLearningService();
        $adaptedPrompt = $learningService->getAdaptedSystemPrompt($conversation->agent);
        $context[] = ['role' => 'system', 'content' => $adaptedPrompt];

        // 4. Inject Personality
        if ($conversation->agent->personality) {
            $context[] = [
                'role' => 'system', 
                'content' => "PERSONALITY INSTRUCTIONS: Adopt the following persona for all responses:\n" . $conversation->agent->personality
            ];
        }

        // 5. Inject Reasoning Protocol (Moved to end for authority)
        $context[] = [
            'role' => 'system',
            'content' => "REASONING PROTOCOL (STRICT MANDATE):
You are an advanced AI. irrespective of your persona, you MUST structure your thinking process using the following XML tags before your final response:
<THOUGHT>Analyze the request and current context.</THOUGHT>
<PLAN>Outline the steps you will take.</PLAN>
<CRITIQUE>Reflect on potential risks or errors in your plan.</CRITIQUE>
<ACTION>Execute the tool calls or final response.</ACTION>

Example:
<THOUGHT>User wants weather.</THOUGHT>
<PLAN>Call weather tool.</PLAN>
<CRITIQUE>Check valid city.</CRITIQUE>
<ACTION>Calling tool...</ACTION>
"
        ];

        foreach ($messages as $msg) {
            // ... (keep vision and content payload logic)
            $content = $msg->content;
            
            if ($msg->role === 'user' && !empty($msg->content) && is_string($msg->content)) {
                // Determine sentiment if previously analyzed (stored in DB)
                // Note: The controller handles the analysis and saving for NEW messages.
                // But for historical context in the prompt, we can use the stored sentiment.
                
                if ($msg->sentiment && $msg->sentiment !== 'neutral') {
                    $context[] = [
                        'role' => 'system',
                        'content' => "User Sentiment for above message: {$msg->sentiment} ({$msg->sentiment_score}). Adjust tone accordingly."
                    ];
                }
            }
            
            if (!empty($msg->images)) {
                $contentPayload = [];
                if (!empty($msg->content)) {
                    $contentPayload[] = ['type' => 'text', 'text' => $msg->content];
                }
                foreach ($msg->images as $imgPath) {
                    $absPath = storage_path('app/public/' . str_replace('chat-images/', '', basename($imgPath)));
                    if (file_exists($absPath)) {
                        $base64 = base64_encode(file_get_contents($absPath));
                        $mime = mime_content_type($absPath);
                        $contentPayload[] = [
                            'type' => 'image_url',
                            'image_url' => ['url' => "data:$mime;base64,$base64"]
                        ];
                    }
                }
                $content = $contentPayload;
            }

            $payload = ['role' => $msg->role, 'content' => $content];
            if ($msg->tool_calls) $payload['tool_calls'] = $msg->tool_calls;
            if ($msg->tool_call_id) $payload['tool_call_id'] = $msg->tool_call_id;
            $context[] = $payload;
        }

        // Prepare Tools filtered by agent config
        $availableTools = [];
        $agentTools = $conversation->agent->tools_config ?? [];
        
        foreach ($this->tools as $tool) {
            // Special check: if no config, allow all. If config exists, filter.
            if (!empty($agentTools) && !in_array($tool->name(), $agentTools)) {
                continue;
            }

            // Inject conversation context if the tool needs it
            if (method_exists($tool, 'setConversation')) {
                $tool->setConversation($conversation);
            }

            $availableTools[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool->name(),
                    'description' => $tool->description(),
                    'parameters' => $tool->parameters(),
                ]
            ];
        }

        // Call AI
        try {
            $lastUserMsg = $messages->where('role', 'user')->last();
            $cacheHit = null;

            // 1. Check Inference Cache (Zero-Latency)
            if ($lastUserMsg && empty($msg->images)) { // Don't cache image queries for now
                 $inferenceService = new \App\Services\InferenceCacheService();
                 $cacheHit = $inferenceService->lookup($lastUserMsg->content);
            }

            if ($cacheHit) {
                // CACHE HIT: Bypass LLM
                Log::info("⚡️ Zero-Latency Cache Hit for: " . $lastUserMsg->content);
                $response = (object) [
                    'choices' => [
                        (object) [
                            'message' => (object) [
                                'content' => $cacheHit->response,
                                'toolCalls' => null
                            ]
                        ]
                    ]
                ];
            } else {
                // CACHE MISS: Call LLM
                // Dynamic Model Routing
                $router = new \App\Services\AI\ModelRouterService();
                $selectedModel = $router->selectModel($context);

                $params = [
                    'model' => $selectedModel,
                    'messages' => $context,
                ];
                if (!empty($availableTools)) $params['tools'] = $availableTools;
                
                $response = OpenAI::chat()->create($params);
                
                // Store result in cache if applicable (No tool calls, reasonable length)
                $choice = $response->choices[0];
                if ($lastUserMsg && empty($choice->message->toolCalls) && strlen($choice->message->content) > 0) {
                     $inferenceService = new \App\Services\InferenceCacheService();
                     $inferenceService->store($lastUserMsg->content, $choice->message->content);
                }
            }
        } catch (\Exception $e) {
            Log::error("AI Error: " . $e->getMessage());
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'system',
                'content' => "Error: " . $e->getMessage(),
            ]);
            return;
        }
        
        $choice = $response->choices[0];
        $messageContent = $choice->message->content;
        $toolCalls = $choice->message->toolCalls;

        // Save Assistant Message
        $assistantMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $messageContent,
            'tool_calls' => $toolCalls ? array_map(fn($tc) => $tc->toArray(), $toolCalls) : null,
        ]);

        // 6. Capture interaction for continuous learning
        if ($messageContent && !$toolCalls) {
            // Only capture final responses (not tool-calling steps)
            $lastUserMessage = $messages->where('role', 'user')->last();
            if ($lastUserMessage) {
                $collector = new InteractionCollectorService();
                $collector->capture(
                    $conversation,
                    $lastUserMessage->content,
                    $messageContent,
                    $assistantMessage->id
                );
            }
        }

        if ($toolCalls) {
            $monitoring = new MonitoringService();
            
            foreach ($toolCalls as $tc) {
                $functionName = $tc->function->name;
                $arguments = json_decode($tc->function->arguments, true);
                
                // Start Trace
                $trace = $monitoring->startTrace(
                    $conversation->id,
                    $conversation->agent_id,
                    $functionName,
                    $arguments
                );

                try {
                    if (isset($this->tools[$functionName])) {
                        $tool = $this->tools[$functionName];
                        
                        // Inject context if aware
                        if ($tool instanceof ContextAwareToolInterface) {
                            $tool->setConversation($conversation);
                        }

                        // Authorize Agent Context (for sensitive tools like ShellTool)
                        if (method_exists($tool, 'setAgentContext')) {
                            $tool->setAgentContext(true);
                        }
                        
                        $output = $tool->execute($arguments);
                    } else {
                        $output = "Error: Tool '$functionName' not found.";
                    }
                    
                    // End Trace (Success)
                    $monitoring->endTrace($trace, $output, 'success');
                } catch (\Exception $e) {
                    $output = "Error: " . $e->getMessage();
                    // End Trace (Error)
                    $monitoring->endTrace($trace, $output, 'error');
                }

                Message::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'tool',
                    'content' => $output,
                    'tool_call_id' => $tc->id,
                ]);

                // 2. SELF-HEALING TRIGGER
                // If output contains common error keywords, trigger self-heal step
                $errorPatterns = ['Error:', 'failed', 'not found', 'Permission denied', 'exception', 'syntax error'];
                $isError = false;
                foreach ($errorPatterns as $pattern) {
                    if (str_contains($output, $pattern)) {
                        $isError = true;
                        break;
                    }
                }

                if ($isError && !str_contains($functionName, 'debugger')) {
                    \App\Jobs\ProcessSelfHealing::dispatch($conversation, $output);
                    return; // Stop current thinking and pivot to healing
                }
            }

            if ($steps - 1 > 0) {
                 \App\Jobs\ProcessAgentThought::dispatch($conversation);
            }
        }
    }
}
