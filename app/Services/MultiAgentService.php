<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class MultiAgentService
{
    protected AgentService $agentService;

    public function __construct()
    {
        $this->agentService = new AgentService();
    }

    /**
     * Process a message and get responses from multiple agents.
     */
    public function processMultiAgentMessage(Conversation $conversation, string $userMessage): array
    {
        $responses = [];
        $participants = $conversation->participants()->with('agent')->get();

        if ($participants->isEmpty()) {
            return $this->processSingleAgent($conversation, $userMessage);
        }

        // Parse @mentions to determine which agents to engage
        $mentionedAgents = $this->parseMentions($userMessage, $participants);

        // If specific agents are mentioned, only those respond
        // Otherwise, primary agent responds (or all if no primary)
        $respondingAgents = $mentionedAgents->isNotEmpty()
            ? $mentionedAgents
            : $this->getDefaultResponders($participants);

        // Build shared context (all messages including other agents' responses)
        $sharedContext = $this->buildSharedContext($conversation);

        foreach ($respondingAgents as $participant) {
            $agent = $participant->agent;

            // Add context about other agents in the conversation
            $agentContext = $this->buildAgentContext($agent, $participants, $sharedContext);

            // Get response from this agent
            $response = $this->getAgentResponse($agent, $userMessage, $agentContext);

            // Store the response
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $response,
                'metadata' => [
                    'agent_id' => $agent->id,
                    'agent_name' => $agent->name,
                    'color' => $participant->color,
                ],
            ]);

            $responses[] = [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'color' => $participant->color,
                'content' => $response,
                'message_id' => $message->id,
            ];

            // Add this response to shared context for next agent
            $sharedContext[] = [
                'role' => 'assistant',
                'content' => "[{$agent->name}]: {$response}",
            ];
        }

        return $responses;
    }

    /**
     * Parse @agent mentions from user message.
     */
    protected function parseMentions(string $message, $participants): \Illuminate\Support\Collection
    {
        preg_match_all('/@(\w+)/i', $message, $matches);
        $mentions = array_map('strtolower', $matches[1] ?? []);

        if (empty($mentions)) {
            return collect();
        }

        return $participants->filter(function ($participant) use ($mentions) {
            return in_array(strtolower($participant->agent->name), $mentions);
        });
    }

    /**
     * Get default responding agents (primary or all).
     */
    protected function getDefaultResponders($participants)
    {
        $primary = $participants->where('is_primary', true);
        return $primary->isNotEmpty() ? $primary : $participants->take(1);
    }

    /**
     * Build shared context from conversation history.
     */
    protected function buildSharedContext(Conversation $conversation): array
    {
        return $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(function ($msg) {
                $prefix = '';
                if ($msg->role === 'assistant' && isset($msg->metadata['agent_name'])) {
                    $prefix = "[{$msg->metadata['agent_name']}]: ";
                }
                return [
                    'role' => $msg->role,
                    'content' => $prefix . $msg->content,
                ];
            })
            ->toArray();
    }

    /**
     * Build context for a specific agent, including awareness of other agents.
     */
    protected function buildAgentContext(Agent $agent, $participants, array $sharedContext): array
    {
        $otherAgents = $participants->filter(fn($p) => $p->agent_id !== $agent->id)
            ->pluck('agent.name')
            ->toArray();

        $systemNote = '';
        if (!empty($otherAgents)) {
            $names = implode(', ', $otherAgents);
            $systemNote = "\n\n[System: You are in a multi-agent conversation with: {$names}. You can see their messages and collaborate with them. The user may address you specifically with @{$agent->name}.]";
        }

        return [
            'system_context' => $systemNote,
            'messages' => $sharedContext,
        ];
    }

    /**
     * Get response from a single agent via API.
     */
    protected function getAgentResponse(Agent $agent, string $userMessage, array $context): string
    {
        try {
            $messages = [
                ['role' => 'system', 'content' => ($agent->system_prompt ?? 'You are a helpful assistant.') . ($context['system_context'] ?? '')],
            ];

            // Add conversation history
            foreach ($context['messages'] ?? [] as $msg) {
                $messages[] = $msg;
            }

            // Add current user message
            $messages[] = ['role' => 'user', 'content' => $userMessage];

            $response = OpenAI::chat()->create([
                'model' => $agent->model ?? 'gpt-4o-mini',
                'messages' => $messages,
            ]);

            return $response->choices[0]->message->content ?? '';
        } catch (\Exception $e) {
            Log::error("Multi-agent response failed: {$e->getMessage()}");
            return "I'm sorry, I encountered an error.";
        }
    }

    /**
     * Fallback for single-agent conversations.
     */
    protected function processSingleAgent(Conversation $conversation, string $userMessage): array
    {
        $agent = $conversation->agent;
        $response = $this->getAgentResponse($agent, $userMessage, ['messages' => $this->buildSharedContext($conversation)]);

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $response,
        ]);

        return [[
            'agent_id' => $agent->id,
            'agent_name' => $agent->name,
            'color' => '#6366f1',
            'content' => $response,
        ]];
    }
}
