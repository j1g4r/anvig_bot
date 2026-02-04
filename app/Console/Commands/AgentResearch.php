<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ResearchService;

class AgentResearch extends Command
{
    protected $signature = 'agent:research {topic? : Topic to research}';
    protected $description = 'Conduct automated research on a topic.';

    public function handle(ResearchService $service)
    {
        $topicInput = $this->argument('topic');
        
        if (!$topicInput) {
            // Self-Directed: Choose a topic from a backlog or a preset list (Simulated)
            $topics = ['Laravel 11 Features', 'Vue 3 Vapor Mode', 'Rust for PHP Extensions'];
            $topicInput = $topics[array_rand($topics)];
            $this->info("🤖 Autonomous Mode: Selected topic '$topicInput'");
        }

        $this->info("🔍 Researching: $topicInput...");
        
        $topic = $service->explore($topicInput);
        
        $this->info("✅ Research Complete.");
        $this->line("   Findings: {$topic->findings}");
        $this->line("   Relevance: {$topic->relevance_score}");
        $this->line("   Source: {$topic->source_url}");

        if ($topic->relevance_score >= 0.8) {
            $this->info("✨ HIGH RELEVANCE! Added to Proposal List.");
        }
    }
}
