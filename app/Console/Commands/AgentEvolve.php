<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EvolutionService;

class AgentEvolve extends Command
{
    protected $signature = 'agent:evolve';
    protected $description = 'Trigger the agent to analyze the roadmap and scaffold the next evolution cycle.';

    public function handle(EvolutionService $evolver)
    {
        $step = $evolver->getNextEvolutionStep();

        if ($step['status'] === 'complete') {
            $this->info("✨ The Singularity is complete. All cycles finished.");
            return;
        }

        $cycle = $step['cycle'];
        $this->info("🧬 Evolution Triggered: {$cycle['id']}");
        $this->line("   Goal: {$cycle['description']}");
        $this->line("   Agent Instructions: {$step['prompt']}");
        
        // In a real autonomous loop, we would inject this prompt into an Agent session here.
        // For C42, we are just establishing the mechanism.
        // Future: $agentService->runAutonomousSession($step['prompt']);
        
        $this->info("✅ Evolution plan ready for execution.");
    }
}
