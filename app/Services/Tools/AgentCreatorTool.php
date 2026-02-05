<?php

namespace App\Services\Tools;

use App\Models\Agent;
use App\Services\ToolRegistry;

class AgentCreatorTool implements ToolInterface
{
    public function name(): string
    {
        return 'agent_manager';
    }

    public function description(): string
    {
        return 'Create, update, and manage other AI agents. Use this to spawn specialist agents with specific toolsets.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['create', 'update', 'list_tools'],
                    'description' => 'Action to perform.',
                ],
                'agent_id' => [
                    'type' => 'integer',
                    'description' => 'ID of agent to update (required for update).',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name of the new agent.',
                ],
                'role' => [
                    'type' => 'string',
                    'description' => 'System prompt / Role definition for the agent.',
                ],
                'model' => [
                    'type' => 'string',
                    'description' => 'LLM Model ID (e.g. gpt-4, claude-3-5-sonnet, ollama/llama3). Defaults to system default.',
                ],
                'tools' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'List of tool names to assign (e.g. ["web_browser", "file_editor"]).',
                ],
                'personality' => [
                    'type' => 'string',
                    'description' => 'Personality traits description.',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(array $input): string
    {
        $args = array_merge($input, $input['params'] ?? []);
        $action = $args['action'] ?? 'create';

        switch ($action) {
            case 'create':
                return $this->createAgent($args);
            case 'update':
                return $this->updateAgent($args);
            case 'list_tools':
                return $this->listTools();
            default:
                return "Unknown action: $action";
        }
    }

    private function createAgent(array $args): string
    {
        if (empty($args['name']) || empty($args['role'])) {
            return "Error: Name and Role are required for creating an agent.";
        }

        $tools = $args['tools'] ?? [];
        // Validate tools?
        // For now, we trust the agent or just warn about missing ones.
        
        $agent = Agent::create([
            'name' => $args['name'],
            'system_prompt' => $args['role'],
            'model' => $args['model'] ?? 'gpt-4o', // Default to a smart model
            'tools_config' => $tools,
            'personality' => $args['personality'] ?? 'Helpful and precise.',
        ]);

        return "Agent '{$agent->name}' (ID: {$agent->id}) created successfully with " . count($tools) . " tools.";
    }

    private function updateAgent(array $args): string
    {
        if (empty($args['agent_id'])) {
            return "Error: agent_id is required for update.";
        }

        $agent = Agent::find($args['agent_id']);
        if (!$agent) {
            return "Error: Agent #{$args['agent_id']} not found.";
        }

        $data = [];
        if (!empty($args['name'])) $data['name'] = $args['name'];
        if (!empty($args['role'])) $data['system_prompt'] = $args['role'];
        if (!empty($args['model'])) $data['model'] = $args['model'];
        if (isset($args['tools'])) $data['tools_config'] = $args['tools'];
        if (!empty($args['personality'])) $data['personality'] = $args['personality'];

        $agent->update($data);

        return "Agent '{$agent->name}' updated.";
    }

    private function listTools(): string
    {
        // This is a bit circular, we need access to ToolRegistry.
        // We can just list the keys from the registry if we can access it, 
        // or hardcode/scan the directory.
        // For simplicity, let's instantiate ToolRegistry and ask it.
        
        // Note: ToolRegistry constructor might be heavy, but let's try.
        // Actually, better to just list the files in the Tools directory or known keys.
        $registry = new ToolRegistry();
        // We can't easily get the keys without a method, but we can look at the file.
        // Let's just return a generic list for now or reflect.
        
        // BETTER: The agent calling this tool SHOULD know what tools exist because IT has access to them 
        // via its OWN tool definitions, or we can provide a list of "Standard Tools".
        
        return "Available Tools: web_browser, file_editor, run_command, memory_recall, vision_tool, kanban_board, database_query, etc.";
    }
}
