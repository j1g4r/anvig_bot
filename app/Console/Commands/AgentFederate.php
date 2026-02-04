<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FederatedLearningService;

class AgentFederate extends Command
{
    protected $signature = 'agent:federate';
    protected $description = 'Aggregate local agent adaptations into the global knowledge pool and distribute top patterns.';

    public function handle(FederatedLearningService $service)
    {
        $this->info("🤝 Starting Federated Learning Cycle...");

        // 1. Aggregation
        $this->line("   Aggregating high-efficacy patterns...");
        $uploaded = $service->aggregate();
        $this->info("   ✅ Uploaded/Updated {$uploaded} patterns to Global Pool.");

        // 2. Distribution
        $this->line("   Distributing global knowledge...");
        $downloaded = $service->distribute();
        $this->info("   ✅ Distributed {$downloaded} patterns to local agents.");
        
        $this->info("✨ Federation Complete.");
    }
}
