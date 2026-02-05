<?php

namespace App\Services\Tools;

use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class DelegateTool implements ToolInterface
{
    /**
     * The conversation context is injected by AgentService during execution.
     */
    protected ?Conversation $conversation = null;

    public function setConversation(Conversation $conversation)
    {
        $this->conversation = $conversation;
    }

    public function name(): string
    {
        return 'delegate';
    }

    public function description(): string
    {
        return 'Delegate the task to a specialist agent. Agents available: "Jerry" (General), "Researcher" (Web/Search), "Developer" (Code/Files).';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'agent_name' => [
                    'type' => 'string',
                    'description' => 'The name of the agent to delegate to (e.g., "Researcher", "Developer", "Jerry").',
                ],
                'mission' => [
                    'type' => 'string',
                    'description' => 'The specific goal or instruction for the new agent.',
                ],
            ],
            'required' => ['agent_name', 'mission'],
        ];
    }

    public function execute(array $input): string
    {
        $args = array_merge($input, $input['params'] ?? []);
        // Logic for delegation
        $targetAgentName = $args['agent_name'] ?? null;
        $mission = $args['mission'] ?? '';

        // Hallucination Check: Agent trying to use delegate tool for kanban operations?
        if (isset($args['action']) && ($args['action'] === 'kanban_board' || $args['status'])) {
            return "Error: Wrong tool detected. You are trying to update the Kanban board using the 'delegate' tool. Please use the 'kanban_board' tool with action='update_status' instead.";
        }

        if (!$this->conversation) {
            return "Error: Internal system error. Conversation context not provided to tool.";
        }

        if (empty($targetAgentName)) {
            return "Error: 'agent_name' parameter is missing. You must specify which agent to delegate to (Jerry, Researcher, Developer).";
        }

        $targetAgent = Agent::where('name', $targetAgentName)->first();

        if (!$targetAgent) {
            return "Error: Specialist agent '$targetAgentName' not found. Available: Jerry, Researcher, Developer.";
        }

        // 1. Log the delegation
        Log::info("Delegating Conversation #{$this->conversation->id} to {$targetAgent->name}");

        // 2. Update the conversation agent
        $this->conversation->update([
            'agent_id' => $targetAgent->id
        ]);

        // 3. Add the mission as a system message to guide the new agent
        Message::create([
            'conversation_id' => $this->conversation->id,
            'role' => 'system',
            'content' => "HANDOVER MISSION: $mission. You are now the active agent.",
        ]);

        return "SUCCESS: Task delegated to {$targetAgent->name}. Mission: $mission. The new agent has been initialized.";
    }
}
