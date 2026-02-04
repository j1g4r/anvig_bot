<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AgentService;

class ChatWithAgent extends Command
{
    protected $signature = 'agent:chat {agent_id?}';
    protected $description = 'Chat with an autonomous agent conversation loop';

    public function handle(AgentService $agentService)
    {
        $agentId = $this->argument('agent_id');
        
        if (!$agentId) {
            $agent = Agent::firstOrCreate(
                ['name' => 'Jerry'],
                [
                    'model' => env('AGENT_MODEL', 'llama3.2'),
                    'system_prompt' => 'You are Jerry, a helpful local assistant with shell access. Use run_command to execute terminal commands.',
                    'tools_config' => ['run_command'],
                ]
            );
            
            // Sync Model from Env
            if ($agent->model !== env('AGENT_MODEL', 'llama3.2')) {
                $agent->update(['model' => env('AGENT_MODEL', 'llama3.2')]);
                $this->info("Updated Agent Model to: " . $agent->model);
            }

        } else {
            $agent = Agent::findOrFail($agentId);
        }

        $this->info("Starting chat with agent: " . $agent->name);

        $conversation = Conversation::create([
            'agent_id' => $agent->id,
            'title' => 'CLI Chat ' . now(),
        ]);

        $lastMessageId = 0;

        while (true) {
            $input = $this->ask('You');
            
            if ($input === 'exit' || $input === 'quit') {
                break;
            }

            $userMsg = Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $input,
            ]);
            
            $lastMessageId = $userMsg->id;

            $this->comment("Thinking...");

            try {
                // Dispatch Job
                \App\Jobs\ProcessAgentThought::dispatch($conversation);
                
                // Poll for completion (poor man's sync)
                $maxRetries = 60; // 60 seconds wait
                $waiting = true;
                $this->comment("Dispatched to Queue... Waiting for response...");
                
                while($waiting && $maxRetries > 0) {
                    sleep(1);
                    $newCount = Message::where('conversation_id', $conversation->id)->where('id', '>', $lastMessageId)->count();
                    if ($newCount > 0) {
                        $waiting = false;
                    }
                    $maxRetries--;
                }
                
                if ($waiting) {
                    $this->error("Timeout waiting for generic queue response.");
                }

            } catch (\Exception $e) {
                $this->error("Agent Error: " . $e->getMessage());
            }

            $newMessages = Message::where('conversation_id', $conversation->id)
                ->where('id', '>', $lastMessageId)
                ->orderBy('id')
                ->get();

            foreach ($newMessages as $msg) {
                if ($msg->role === 'assistant') {
                    if ($msg->content) {
                        $this->info("Agent: " . $msg->content);
                    }
                    if ($msg->tool_calls) {
                        foreach ($msg->tool_calls as $tc) {
                            $this->line("<Calling Tool: " . $tc['function']['name'] . ">");
                        }
                    }
                } elseif ($msg->role === 'tool') {
                    $this->line("<Tool Output>: " . substr($msg->content, 0, 200) . (strlen($msg->content) > 200 ? '...' : ''));
                }
                $lastMessageId = $msg->id;
            }
        }
    }
}
