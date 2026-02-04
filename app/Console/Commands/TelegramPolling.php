<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SergiX44\Nutgram\Nutgram;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AgentService;
use Illuminate\Support\Facades\Log;

class TelegramPolling extends Command
{
    protected $signature = 'telegram:run';
    protected $description = 'Start the Telegram Bot (Long Polling)';

    public function handle(AgentService $agentService)
    {
        $token = env('TELEGRAM_TOKEN');
        if (!$token) {
            $this->error('TELEGRAM_TOKEN is missing in .env');
            return;
        }

        // Increase timeout for long polling and local dev
        // Increase timeout for long polling and local dev
        $config = new \SergiX44\Nutgram\Configuration(
            clientTimeout: 60,
            pollingTimeout: 30
        );
        $bot = new Nutgram($token, $config);

        $bot->onMessage(function (Nutgram $bot) use ($agentService) {
            $user = $bot->user();
            $text = $bot->message()->text;
            $chatId = $bot->chatId();
            
            $this->info("Received [$chatId]: $text");

            // Find or Create Agent
            $agent = Agent::firstOrCreate(['name' => 'Jerry'], [
                'model' => env('AGENT_MODEL', 'llama3.2'),
                'system_prompt' => "You are Jerry, an autonomous AI agent...",
                'tools_config' => ['run_command'],
            ]);

            // Sync Model from Env
            if ($agent->model !== env('AGENT_MODEL', 'llama3.2')) {
                $agent->update(['model' => env('AGENT_MODEL', 'llama3.2')]);
            }

            // Find or Create Conversation mapped to Telegram Chat ID
            // We use 'metadata' to store specific external IDs
            $conversation = Conversation::where('agent_id', $agent->id)
                ->whereJsonContains('metadata->telegram_chat_id', $chatId)
                ->first();

            if (!$conversation) {
                $conversation = Conversation::create([
                    'agent_id' => $agent->id,
                    'title' => 'Telegram Chat ' . $user->first_name,
                    'metadata' => ['telegram_chat_id' => $chatId]
                ]);
            }

            // Save User Message
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $text,
            ]);

            $bot->sendChatAction('typing');

            // Run Agent (Synchronous for now, ideally queue)
            try {
                // We need a way to capture the output and send it back to Telegram.
                // The current AgentService saves to DB but doesn't return the new messages comfortably.
                // We can fetch the latest messages after run.
                
                $lastMsgId = $conversation->messages()->max('id');
                
                // Dispatch Job
                \App\Jobs\ProcessAgentThought::dispatch($conversation);
                
                // Poll for completion
                $maxRetries = 60; // 60 seconds wait
                $waiting = true;
                
                while($waiting && $maxRetries > 0) {
                    sleep(1);
                    $newMessages = $conversation->messages()
                        ->where('id', '>', $lastMsgId)
                        ->orderBy('id', 'asc')
                        ->get();

                    if ($newMessages->isNotEmpty()) {
                         foreach ($newMessages as $msg) {
                            $lastMsgId = $msg->id; // Update pointer
                            if ($msg->role === 'assistant') {
                                if ($msg->content) {
                                    $bot->sendMessage($msg->content);
                                }
                                if ($msg->tool_calls) {
                                    foreach ($msg->tool_calls as $tool) {
                                        $bot->sendMessage("🛠 Executing: " . $tool['function']['name']);
                                    }
                                }
                            } elseif ($msg->role === 'tool') {
                                $output = $msg->content;
                                if (strlen($output) > 2000) {
                                    $output = substr($output, 0, 2000) . "\n...(truncated)";
                                }
                                $bot->sendMessage("Creating Tool Output:\n```\n" . $output . "\n```", ['parse_mode' => 'Markdown']);
                            } elseif ($msg->role === 'system') {
                                 $bot->sendMessage("⚠️ System: " . $msg->content);
                            }
                        }
                        
                        // Heuristic: If we got an assistant message without tool calls, we might be done. 
                        // But wait, the loop might continue.
                        // The Job is recursive.
                        // For Polling, we just listen for a bit and then give up or wait for next user input?
                        // Actually, if we use Reverb later, we don't need this loop.
                        // For now, let's just listen for 60 seconds collecting everything.
                        // But if 5 seconds pass without ANY new message, maybe we stop?
                        // Let's assume the Job finishes reasonably fast.
                        
                    }
                    $maxRetries--;
                }

            } catch (\Exception $e) {
                $bot->sendMessage("Error: " . $e->getMessage());
                Log::error($e);
            }
        });

        $this->info("Telegram Bot Started...");
        $bot->run();
    }
}
