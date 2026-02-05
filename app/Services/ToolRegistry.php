<?php

namespace App\Services;

use App\Models\Agent;
use App\Services\Tools\ToolInterface;

class ToolRegistry
{
    protected array $tools = [];

    public function __construct()
    {
        $this->registerDefaultTools();
    }

    protected function registerDefaultTools()
    {
        // Register all available tools
        // We assume these classes exist in App\Services\Tools namespace
        $toolClasses = [
            \App\Services\Tools\ShellTool::class,
            \App\Services\Tools\FileTool::class,
            \App\Services\Tools\BrowserTool::class,
            \App\Services\Tools\MemoryTool::class,
            \App\Services\Tools\ResearchTool::class,
            \App\Services\Tools\DatabaseTool::class,
            \App\Services\Tools\DelegateTool::class,
            \App\Services\Tools\WebhookTool::class,
            \App\Services\Tools\CanvasTool::class,
            \App\Services\Tools\ScheduleTool::class,
            \App\Services\Tools\DesktopTool::class,
            // GraphTool requires dependency injection usually, keeping simple for now or using container
           // \App\Services\Tools\GraphTool::class, 
            \App\Services\Tools\DebuggerTool::class,
            \App\Services\Tools\KanbanTool::class,
            \App\Services\Tools\ComplianceTool::class,
            \App\Services\Tools\MfaTool::class,
            \App\Services\Tools\RagTool::class,
            \App\Services\Tools\SmartHomeTool::class,
            \App\Services\Tools\LearningTool::class,
            \App\Services\Tools\NotifyTool::class,
            \App\Services\Tools\RoadmapTool::class,
            \App\Services\Tools\CollaborateTool::class,
            \App\Services\Tools\CodeAnalyzerTool::class,
            \App\Services\Tools\AgentCreatorTool::class,
        ];

        foreach ($toolClasses as $class) {
            if (class_exists($class)) {
                // Handle GraphTool special case or general DI resolution
                if ($class === \App\Services\Tools\GraphTool::class) {
                    $this->register(new $class(new \App\Services\KnowledgeService()));
                } else {
                    $this->register(new $class());
                }
            }
        }
        
        // Register GraphTool manually if class exists (handling dependency)
        if (class_exists(\App\Services\Tools\GraphTool::class)) {
             $this->register(new \App\Services\Tools\GraphTool(new \App\Services\KnowledgeService()));
        }

        // Omnibus Upgrade
        $this->register(new Tools\FileEditorTool());
        $this->register(new Tools\GitTool());
        $this->register(new Tools\SequentialThinkingTool());
        $this->register(new Tools\TestRunnerTool());
        $this->register(new Tools\WriterTool());
        $this->register(new Tools\VisionTool(app(\App\Services\VisionService::class)));
        $this->register(new Tools\DeployerTool());
        if (env('SLACK_BOT_TOKEN')) {
            $this->register(new Tools\SlackTool());
        }

        // Bonus Tools
        $this->register(new Tools\SecurityAuditorTool());

        if (env('BINANCE_API_KEY')) {
            $this->register(new Tools\CryptoTraderTool());
        }
        
        $this->register(new Tools\VoiceNodeTool());
        $this->register(new Tools\ChaosMonkeyTool());

        if (env('SERP_API_KEY')) {
            $this->register(new Tools\GoogleSearchTool());
        }

        $this->register(new Tools\PythonReplTool());
        
        if (env('GITHUB_TOKEN')) {
             $this->register(new Tools\GitHubTool());
        }
        
        $this->register(new Tools\CommunicationTool());
    }

    protected function register(ToolInterface $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    /**
     * Get tool definition (JSON schema) for the AI model.
     */
    public function getToolsForAgent(\App\Models\Agent $agent): array
    {
        $definitions = [];
        $agentTools = $agent->tools_config ?? []; // e.g. ['browse_web', 'file_system']

        // Global tools for everyone? Or force config?
        // Let's allow 'sequential_thinking' for everyone by default if we want
        if (!in_array('sequential_thinking', $agentTools)) { 
             $agentTools[] = 'sequential_thinking'; 
        }

        foreach ($this->tools as $name => $tool) {
            // Strict Mode: Only show tools assigned to this agent
            if (in_array($name, $agentTools)) {
                 $definitions[] = [
                    'type' => 'function',
                    'function' => [
                        'name' => $tool->name(),
                        'description' => $tool->description(),
                        'parameters' => [
                            'type' => 'object',
                            'properties' => [
                                'action' => ['type' => 'string'],
                                // Dynamic params... simplified for now
                                'params' => ['type' => 'object'], 
                                // ... specific tool schemas could be better defined in the Tool classes themselves
                            ]
                        ]
                    ]
                ];
            }
        }
        
        return $definitions;
    }
    
    /**
     * Execute a tool by name.
     */
    public function execute(string $toolName, array $parameters, ?\App\Models\Conversation $conversation = null): string
    {
        if (!isset($this->tools[$toolName])) {
            throw new \Exception("Tool '{$toolName}' not found or not registered.");
        }

        $tool = $this->tools[$toolName];

        // Authorize Agent Context (for sensitive tools like ShellTool)
        if (method_exists($tool, 'setAgentContext')) {
            $tool->setAgentContext(true);
        }

        // Inject Conversation Context (for collaborative tools)
        if ($conversation && method_exists($tool, 'setConversation')) {
            $tool->setConversation($conversation);
        }

        return $tool->execute($parameters);
    }
}
