<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\AgentAdaptation;
use App\Models\LearningExample;
use App\Services\ContinuousLearningService;
use Illuminate\Console\Command;

class AnalyzePerformance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'agent:analyze-performance 
                            {agent_id : ID of agent to analyze}
                            {--period=30 : Analysis period in days}';

    /**
     * The console command description.
     */
    protected $description = 'Analyze agent performance and provide insights';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $agentId = $this->argument('agent_id');
        $period = (int) $this->option('period');

        $agent = Agent::find($agentId);
        if (!$agent) {
            $this->error("Agent #{$agentId} not found.");
            return 1;
        }

        $learningService = new ContinuousLearningService();
        $insights = $learningService->getInsights($agent);

        $this->newLine();
        $this->info("╔══════════════════════════════════════════════════════════╗");
        $this->info("║       AGENT PERFORMANCE ANALYSIS                          ║");
        $this->info("╠══════════════════════════════════════════════════════════╣");
        $this->info("║  Agent: {$agent->name} (#{$agent->id})");
        $this->info("║  Period: Last {$period} days");
        $this->info("╚══════════════════════════════════════════════════════════╝");
        $this->newLine();

        // Overview Table
        $this->info("📊 OVERVIEW");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Interactions', $insights['total_examples']],
                ['Satisfaction Rate', $insights['satisfaction_rate'] . '%'],
                ['Active Adaptations', $insights['active_adaptations']],
                ['Learning Sessions', $insights['completed_sessions'] . '/' . $insights['total_sessions']],
            ]
        );

        // Feedback Distribution
        $this->newLine();
        $this->info("👍 FEEDBACK DISTRIBUTION");
        $dist = $insights['feedback_distribution'];
        $total = max(1, $dist['positive'] + $dist['negative'] + $dist['neutral']);
        
        $positiveBar = str_repeat('█', min(40, (int)($dist['positive'] / $total * 40)));
        $negativeBar = str_repeat('█', min(40, (int)($dist['negative'] / $total * 40)));
        $neutralBar = str_repeat('█', min(40, (int)($dist['neutral'] / $total * 40)));
        
        $this->line("  <fg=green>Positive ({$dist['positive']})</>: " . $positiveBar);
        $this->line("  <fg=red>Negative ({$dist['negative']})</>: " . $negativeBar);
        $this->line("  <fg=gray>Neutral ({$dist['neutral']})</>: " . $neutralBar);

        // Trend
        $this->newLine();
        $this->info("📈 TREND ANALYSIS");
        $trend = $insights['trend'];
        $trendIcon = match($trend['direction']) {
            'improving' => '⬆️',
            'declining' => '⬇️',
            default => '➡️',
        };
        $this->line("  Recent avg score: {$trend['recent_avg']}");
        $this->line("  Previous avg score: {$trend['previous_avg']}");
        $this->line("  Direction: {$trendIcon} {$trend['direction']}");

        // Active Adaptations
        $this->newLine();
        $this->info("🧬 ACTIVE ADAPTATIONS");
        $adaptations = AgentAdaptation::where('agent_id', $agentId)
            ->active()
            ->ordered()
            ->get();

        if ($adaptations->isEmpty()) {
            $this->warn("  No active adaptations. Run 'php artisan agent:learn {$agentId}' to generate.");
        } else {
            $rows = $adaptations->map(fn($a) => [
                $a->name,
                $a->adaptation_type,
                round($a->weight, 2),
                $a->effectiveness ? round($a->effectiveness, 2) : 'N/A',
            ])->toArray();
            
            $this->table(['Name', 'Type', 'Weight', 'Effectiveness'], $rows);
        }

        // Recommendations
        $this->newLine();
        $this->info("💡 RECOMMENDATIONS");
        
        if ($insights['satisfaction_rate'] < 50) {
            $this->warn("  ⚠️ Critical: Satisfaction rate below 50%. Review negative feedback urgently.");
        } elseif ($insights['satisfaction_rate'] < 70) {
            $this->line("  📌 Improvement needed: Run learning session to identify patterns.");
        } else {
            $this->line("  ✅ Good performance. Continue monitoring.");
        }

        if ($insights['active_adaptations'] === 0) {
            $this->line("  📌 No learned behaviors yet. Run: php artisan agent:learn {$agentId}");
        }

        if ($trend['direction'] === 'declining') {
            $this->warn("  ⚠️ Performance declining. Analyze recent negative feedback.");
        }

        if ($insights['last_learning']) {
            $this->line("  📅 Last learning session: " . $insights['last_learning']->diffForHumans());
        }

        $this->newLine();
        
        return 0;
    }
}
