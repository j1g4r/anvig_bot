<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Services\ContinuousLearningService;
use Illuminate\Console\Command;

class AgentLearn extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'agent:learn 
                            {agent_id? : ID of agent to train (optional, trains all if not specified)}
                            {--limit=100 : Maximum examples to process per agent}
                            {--detailed : Show detailed output}';

    /**
     * The console command description.
     */
    protected $description = 'Run a learning session to extract patterns from user feedback';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $agentId = $this->argument('agent_id');
        $limit = (int) $this->option('limit');
        $detailed = $this->option('detailed');

        $learningService = new ContinuousLearningService();

        $agents = $agentId 
            ? Agent::where('id', $agentId)->get()
            : Agent::all();

        if ($agents->isEmpty()) {
            $this->error($agentId ? "Agent #{$agentId} not found." : "No agents found.");
            return 1;
        }

        $this->info("🧠 Starting learning session for " . $agents->count() . " agent(s)...\n");

        foreach ($agents as $agent) {
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("📚 Agent: {$agent->name} (#{$agent->id})");
            
            try {
                $session = $learningService->learn($agent, $limit);

                if ($session->isCompleted()) {
                    $this->info("   ✅ Session completed successfully");
                    $this->info("   📊 Examples processed: {$session->examples_processed}");
                    
                    if ($detailed && $session->improvements) {
                        $this->info("   📝 Improvements:");
                        foreach ($session->improvements as $improvement) {
                            $this->line("      - {$improvement}");
                        }
                    }

                    if ($session->metrics) {
                        $metrics = $session->metrics;
                        $this->info("   📈 Metrics:");
                        $this->line("      Positive examples: " . ($metrics['positive_examples'] ?? 0));
                        $this->line("      Negative examples: " . ($metrics['negative_examples'] ?? 0));
                        $this->line("      Patterns extracted: " . ($metrics['patterns_extracted'] ?? 0));
                        $this->line("      Adaptations created: " . ($metrics['adaptations_created'] ?? 0));
                    }
                } else {
                    $this->warn("   ⚠️ Session status: {$session->status}");
                    if ($session->error_message) {
                        $this->error("   Error: {$session->error_message}");
                    }
                }
            } catch (\Exception $e) {
                $this->error("   ❌ Failed: {$e->getMessage()}");
            }

            $this->newLine();
        }

        $this->info("🎉 Learning session completed!");
        
        return 0;
    }
}
