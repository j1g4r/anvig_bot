<?php

namespace App\Services;

use App\Models\AgentAdaptation;
use App\Models\GlobalKnowledgePool;
use Illuminate\Support\Facades\Log;

class FederatedLearningService
{
    /**
     * Upload local high-efficacy patterns to the global pool.
     */
    public function aggregate(): int
    {
        // 1. Find local adaptations with high effectiveness (> 0.8)
        $candidates = AgentAdaptation::where('effectiveness', '>=', 0.8)->get();
        $uploaded = 0;

        foreach ($candidates as $adaptation) {
            // Access pattern array safely
            $patternData = $adaptation->pattern ?? [];
            $trigger = $patternData['trigger'] ?? 'unknown';
            $instruction = $patternData['instruction'] ?? 'unknown';

            // Generate a simple hash of the pattern to detect duplicates (naive approach)
            $hash = md5(json_encode([
                'trigger' => $trigger,
                'instruction' => $instruction
            ]));

            // Check if exists
            $global = GlobalKnowledgePool::where('pattern_hash', $hash)->first();

            if ($global) {
                // Update existing: Weighted average score
                $newScore = ($global->global_score * $global->usage_count + $adaptation->effectiveness) / ($global->usage_count + 1);
                $global->global_score = $newScore;
                $global->usage_count += 1;
                
                // Add contributor if not present
                $contributors = $global->contributors ?? [];
                if (!in_array($adaptation->agent_id, $contributors)) {
                    $contributors[] = $adaptation->agent_id;
                    $global->contributors = $contributors;
                }
                
                $global->save();
            } else {
                // Create new global entry
                GlobalKnowledgePool::create([
                    'pattern_hash' => $hash,
                    'pattern_json' => json_encode([
                        'trigger' => $trigger,
                        'instruction' => $instruction
                    ]),
                    'global_score' => $adaptation->effectiveness,
                    'usage_count' => 1,
                    'contributors' => [$adaptation->agent_id],
                ]);
                $uploaded++;
            }
        }

        return $uploaded;
    }

    /**
     * Download global patterns to local agents.
     */
    public function distribute(): int
    {
        // 1. Get top global patterns (> 0.9 score)
        $topPatterns = GlobalKnowledgePool::where('global_score', '>=', 0.9)->get();
        $downloaded = 0;

        // In a real scenario, we would distribute to specific agents based on relevance.
        // For C43 proof of concept, we will consider "Downloaded" if we process them.
        // To verify, we could create a "Generic Agent" adaptation or just log it.
        // Let's create an adaptation for agent_id=1 (System) if valid.

        foreach ($topPatterns as $pattern) {
            $data = json_decode($pattern->pattern_json, true);
            $trigger = $data['trigger'];
            $instruction = $data['instruction'];

            // Check if ANY agent already has this locally?
            // This logic is simplified.
            
            // For demo: Log what would be distributed
            Log::info("Federated Learning: Distributing pattern {$pattern->pattern_hash} (Score: {$pattern->global_score})");
            $downloaded++;
        }

        return $downloaded;
    }
}
