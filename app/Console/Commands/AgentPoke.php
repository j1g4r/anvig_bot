<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\Message;
use App\Jobs\ProcessAgentThought;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AgentPoke extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agent:poke {--force : Force a poke even if the conversation is old}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger an autonomous reasoning cycle for active conversations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting autonomous agent poke...');

        // Find active conversations that had activity in the last 24 hours
        // and whose last message was from the assistant or user (i.e. not already being processed)
        $conversations = Conversation::where('status', 'active')
            ->where('updated_at', '>=', now()->subHours(24))
            ->get();

        if ($conversations->isEmpty()) {
            $this->info('No active conversations found to poke.');
            return;
        }

        foreach ($conversations as $conversation) {
            $this->info("Poking Conversation #{$conversation->id}: {$conversation->title}");

            // Add a "Passive Observation" system message
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'system',
                'content' => "SYSTEM WAKE-UP: It is now " . now()->toDateTimeString() . ". Perform an environmental audit or check for pending tasks. If no action is needed, simply acknowledge and return to sleep.",
            ]);

            // Dispatch the thought process
            ProcessAgentThought::dispatch($conversation);
        }

        $this->info('Poke complete. Reasoning loops dispatched.');
    }
}
