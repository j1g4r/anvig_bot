<?php

namespace App\Services\Tools;

use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class CollaborateTool implements ToolInterface
{
    protected ?Conversation $conversation = null;

    public function setConversation(Conversation $conversation)
    {
        $this->conversation = $conversation;
    }

    public function name(): string
    {
        return 'invite_agent';
    }

    public function description(): string
    {
        return 'Invite another agent into the conversation to collaborate. Available agents: "Jerry" (Manager), "Researcher" (Web), "Developer" (Code).';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'agent_name' => [
                    'type' => 'string',
                    'description' => 'The name of the agent to invite.',
                ],
                'reason' => [
                    'type' => 'string',
                    'description' => 'Why you are inviting them (context for the new agent).',
                ],
            ],
            'required' => ['agent_name', 'reason'],
        ];
    }

    public function execute(array $input): string
    {
        $args = array_merge($input, $input['params'] ?? []);
        $agentName = $args['agent_name'] ?? '';
        $reason = $args['reason'] ?? '';

        if (!$this->conversation) {
            return "Error: Conversation context missing.";
        }

        $targetAgent = Agent::where('name', $agentName)->first();

        if (!$targetAgent) {
            return "Error: Agent '$agentName' not found.";
        }

        // Check if already in chat
        if ($this->conversation->participants()->where('agent_id', $targetAgent->id)->exists()) {
            return "Info: Agent '$agentName' is already in this conversation.";
        }

        // Add to participants
        $this->conversation->addParticipant($targetAgent);
        
        // Mark conversation as multi-agent
        $this->conversation->update(['is_multi_agent' => true]);

        // Announce arrival
        Message::create([
            'conversation_id' => $this->conversation->id,
            'role' => 'system',
            'content' => "📢 SYSTEM ANNOUNCEMENT: {$targetAgent->name} has joined the chat.\nReason: \"$reason\"\nYou can now address them directly.",
        ]);

        return "Success: {$targetAgent->name} has been invited.";
    }
}
