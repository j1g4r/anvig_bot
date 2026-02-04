<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Services\AgentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAgentThought implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes timeout for LLM

    /**
     * Create a new job instance.
     */
    public function __construct(protected Conversation $conversation)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(AgentService $agentService): void
    {
        Log::info("Processing Agent Thought for Conversation ID: " . $this->conversation->id);
        
        event(new \App\Events\AgentThinking($this->conversation));

        try {
            $agentService->run($this->conversation);
        } catch (\Exception $e) {
            Log::error("Job Failed: " . $e->getMessage());
            // Optionally store error in DB
        }
    }
}
