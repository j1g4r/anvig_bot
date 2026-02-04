<?php

namespace App\Services;

use App\Models\ResearchTopic;
use Illuminate\Support\Facades\Log;

class ResearchService
{
    /**
     * Explore a topic and update the database.
     */
    public function explore(string $topicName): ResearchTopic
    {
        $topic = ResearchTopic::firstOrCreate(
            ['topic' => $topicName],
            ['status' => 'pending']
        );

        // Simulate Research Process (since we can't easily browse the live web autonomously in this simplified service,
        // we will simulate the findings or use a placeholder logic. In production, this would call SearchTool or LLM.)
        
        $findings = $this->simulateResearch($topicName);
        
        $topic->update([
            'status' => 'researched',
            'findings' => $findings['summary'],
            'relevance_score' => $findings['score'],
            'source_url' => $findings['url'],
        ]);

        return $topic;
    }

    /**
     * Check if a topic is highly relevant.
     */
    public function propose(): array
    {
        return ResearchTopic::where('status', 'researched')
            ->where('relevance_score', '>=', 0.8)
            ->get()
            ->toArray();
    }

    private function simulateResearch(string $topic): array
    {
        // Simple heuristic for demo purposes
        $score = 0.5;
        $summary = "Research findings for $topic. ";
        
        if (str_contains(strtolower($topic), 'laravel')) {
            $score = 0.9;
            $summary .= "This technology is highly aligned with our stack. Recommendation: Adopt.";
        } elseif (str_contains(strtolower($topic), 'optimization')) {
            $score = 0.85;
            $summary .= "Performance improvements are critical. Worth investigating.";
        } else {
            $summary .= "General relevance seems moderate.";
        }

        return [
            'summary' => $summary,
            'score' => $score,
            'url' => 'https://google.com/search?q=' . urlencode($topic),
        ];
    }
}
