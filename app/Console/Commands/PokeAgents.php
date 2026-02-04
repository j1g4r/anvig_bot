<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Conversation;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PokeAgents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agent:poke {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Health Check: Nudge active agents that have gone silent to ensure they are still working.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Find active conversations updated more than 30 minutes ago
        $cutoff = Carbon::now()->subMinutes(30);
        
        $idleConversations = Conversation::where('status', 'active')
            ->where('updated_at', '<', $cutoff)
            ->get();

        if ($idleConversations->isEmpty()) {
            $this->info("No idle agents found.");
            return 0;
        }

        foreach ($idleConversations as $conversation) {
            $this->info("Poking Agent in Conversation #{$conversation->id}...");
            Log::info("Health Check: Poking idle agent in conversation #{$conversation->id}");

            // 1. Inject System Message Nudge
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'system',
                'content' => "SYSTEM ALERT: No activity detected for 30+ minutes. Please report your status. Are you stuck or waiting for something? If the task is done, please mark it as complete.",
            ]);

            // 2. Trigger Wake Up Call
            \App\Jobs\ProcessAgentThought::dispatch($conversation);
        }

        $this->info(" poked " . $idleConversations->count() . " agents.");
        return 0;
    }
}
