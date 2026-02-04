<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EnsureAgentsActive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agent:ensure-active';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vigorously checks for active agents that have stalled and restarts them.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Get Active Conversations not updated in last 2 minutes
        // We use 2 minutes as a safe buffer. 
        // If an agent is "thinking" for > 2 mins without a trace, it's likely stuck or queue is full.
        $stalledTime = Carbon::now()->subMinutes(2);
        
        $stalledConversations = Conversation::where('status', 'active')
            ->where('updated_at', '<', $stalledTime)
            ->get();

        if ($stalledConversations->count() > 0) {
            $this->info("Found {$stalledConversations->count()} stalled active conversations.");
            
            foreach ($stalledConversations as $conversation) {
                $this->info("Restaring agent checks for Conversation ID: {$conversation->id}");
                Log::warning("Agent Stalled Detected: Conversation #{$conversation->id}. Forcing restart.");
                
                // Dispatch job immediately. 
                // We do NOT add a message to avoid spamming context. 
                // Just triggering the job is enough to pick up where it left off.
                \App\Jobs\ProcessAgentThought::dispatch($conversation);
                
                // Touch the conversation so it doesn't get picked up again immediately in next run
                $conversation->touch();
            }
        } else {
            $this->info("All active agents appear healthy.");
        }
    }
}
