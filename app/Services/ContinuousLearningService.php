<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentAdaptation;
use App\Models\LearningExample;
use App\Models\LearningSession;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class ContinuousLearningService
{
    protected EmbeddingService $embeddingService;

    public function __construct()
    {
        $this->embeddingService = new EmbeddingService();
    }

    /**
     * Run a learning session for an agent.
     */
    public function learn(Agent $agent, int $limit = 100): LearningSession
    {
        $session = LearningSession::create([
            'agent_id' => $agent->id,
            'started_at' => now(),
            'status' => 'running',
        ]);

        try {
            // Get recent examples with feedback
            $examples = LearningExample::where('agent_id', $agent->id)
                ->whereNot('feedback_score', 0)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();

            if ($examples->isEmpty()) {
                $session->markCompleted(['message' => 'No examples with feedback found']);
                return $session;
            }

            // Analyze patterns from examples
            $patterns = $this->analyzePatterns($examples);
            $session->incrementProcessed($examples->count());

            // Create/update adaptations
            $improvements = $this->createAdaptations($agent, $patterns);

            $session->markCompleted($improvements, [
                'positive_examples' => $examples->where('feedback_score', '>', 0)->count(),
                'negative_examples' => $examples->where('feedback_score', '<', 0)->count(),
                'patterns_extracted' => count($patterns),
                'adaptations_created' => count($improvements),
            ]);

        } catch (\Exception $e) {
            Log::error("Learning session failed: {$e->getMessage()}");
            $session->markFailed($e->getMessage());
        }

        return $session;
    }

    /**
     * Analyze examples to extract patterns.
     */
    protected function analyzePatterns($examples): array
    {
        // Group by positive and negative
        $positive = $examples->filter(fn($e) => $e->feedback_score > 0);
        $negative = $examples->filter(fn($e) => $e->feedback_score < 0);

        $patterns = [];

        // Use AI to analyze patterns
        if ($positive->count() >= 3) {
            $positivePattern = $this->extractPattern($positive, 'positive');
            if ($positivePattern) {
                $patterns[] = [
                    'type' => AgentAdaptation::TYPE_STYLE,
                    'source' => 'positive',
                    ...$positivePattern,
                ];
            }
        }

        if ($negative->count() >= 3) {
            $negativePattern = $this->extractPattern($negative, 'negative');
            if ($negativePattern) {
                $patterns[] = [
                    'type' => AgentAdaptation::TYPE_RULE,
                    'source' => 'negative',
                    ...$negativePattern,
                ];
            }
        }

        return $patterns;
    }

    /**
     * Use AI to extract a pattern from examples.
     */
    protected function extractPattern($examples, string $type): ?array
    {
        $exampleTexts = $examples->take(10)->map(function ($e) {
            return "Input: {$e->user_input}\nOutput: {$e->agent_output}\nScore: {$e->feedback_score}";
        })->implode("\n---\n");

        $prompt = $type === 'positive'
            ? "Analyze these highly-rated interactions and identify what made them successful. Extract a single, clear behavioral instruction that captures the effective pattern. Be specific and actionable."
            : "Analyze these poorly-rated interactions and identify what went wrong. Extract a single, clear instruction on what to AVOID. Be specific and actionable.";

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an AI behavior analyst. Extract actionable patterns from interaction data. Respond in JSON format: {"name": "short_name", "instruction": "clear behavioral instruction", "confidence": 0.0-1.0}'],
                    ['role' => 'user', 'content' => "{$prompt}\n\nExamples:\n{$exampleTexts}"],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            $result = json_decode($response->choices[0]->message->content, true);
            
            if (isset($result['instruction'])) {
                return [
                    'name' => $result['name'] ?? ($type === 'positive' ? 'Learned Success Pattern' : 'Learned Avoidance Rule'),
                    'instruction' => $result['instruction'],
                    'confidence' => $result['confidence'] ?? 0.7,
                ];
            }
        } catch (\Exception $e) {
            Log::warning("Pattern extraction failed: {$e->getMessage()}");
        }

        return null;
    }

    /**
     * Create adaptations from extracted patterns.
     */
    protected function createAdaptations(Agent $agent, array $patterns): array
    {
        $improvements = [];

        foreach ($patterns as $pattern) {
            // Check for existing similar adaptation
            $existing = AgentAdaptation::where('agent_id', $agent->id)
                ->where('adaptation_type', $pattern['type'])
                ->where('name', $pattern['name'])
                ->first();

            if ($existing) {
                // Update existing
                $existing->update([
                    'pattern' => ['instruction' => $pattern['instruction']],
                    'weight' => min(1.0, $existing->weight + 0.1), // Increase weight
                ]);
                $improvements[] = "Updated: {$pattern['name']}";
            } else {
                // Create new
                AgentAdaptation::create([
                    'agent_id' => $agent->id,
                    'adaptation_type' => $pattern['type'],
                    'name' => $pattern['name'],
                    'description' => "Learned from {$pattern['source']} feedback",
                    'pattern' => ['instruction' => $pattern['instruction']],
                    'weight' => $pattern['confidence'] ?? 0.7,
                    'active' => true,
                ]);
                $improvements[] = "Created: {$pattern['name']}";
            }
        }

        return $improvements;
    }

    /**
     * Get adapted system prompt with learned behaviors injected.
     */
    public function getAdaptedSystemPrompt(Agent $agent): string
    {
        $basePrompt = $agent->system_prompt ?? 'You are a helpful assistant.';
        
        $adaptations = AgentAdaptation::where('agent_id', $agent->id)
            ->active()
            ->ordered()
            ->limit(5) // Limit to avoid prompt bloat
            ->get();

        if ($adaptations->isEmpty()) {
            return $basePrompt;
        }

        $learnings = $adaptations->map(fn($a) => "- " . $a->toPromptInstruction())->implode("\n");

        return $basePrompt . "\n\n[LEARNED BEHAVIORS - Apply these patterns from past interactions]\n" . $learnings;
    }

    /**
     * Get insights about agent learning.
     */
    public function getInsights(Agent $agent): array
    {
        $examples = LearningExample::where('agent_id', $agent->id);
        $adaptations = AgentAdaptation::where('agent_id', $agent->id);
        $sessions = LearningSession::where('agent_id', $agent->id);

        // Calculate feedback distribution
        $positive = (clone $examples)->where('feedback_score', '>', 0)->count();
        $negative = (clone $examples)->where('feedback_score', '<', 0)->count();
        $neutral = (clone $examples)->where('feedback_score', 0)->count();
        $total = $positive + $negative + $neutral;

        // Recent trend (last 7 days vs previous 7 days)
        $recentAvg = (clone $examples)
            ->where('created_at', '>=', now()->subDays(7))
            ->avg('feedback_score') ?? 0;
        
        $previousAvg = (clone $examples)
            ->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])
            ->avg('feedback_score') ?? 0;

        return [
            'total_examples' => $total,
            'feedback_distribution' => [
                'positive' => $positive,
                'negative' => $negative,
                'neutral' => $neutral,
            ],
            'satisfaction_rate' => $total > 0 ? round(($positive / $total) * 100, 1) : 0,
            'active_adaptations' => $adaptations->active()->count(),
            'total_sessions' => $sessions->count(),
            'completed_sessions' => $sessions->where('status', 'completed')->count(),
            'trend' => [
                'recent_avg' => round($recentAvg, 3),
                'previous_avg' => round($previousAvg, 3),
                'direction' => $recentAvg > $previousAvg ? 'improving' : ($recentAvg < $previousAvg ? 'declining' : 'stable'),
            ],
            'last_learning' => $sessions->latest()->first()?->completed_at,
        ];
    }

    /**
     * Score an interaction based on patterns (for auto-scoring).
     */
    public function scoreInteraction(string $input, string $output, Agent $agent): float
    {
        // Basic heuristics for auto-scoring
        $score = 0.0;

        // Positive signals
        if (strlen($output) > 50) $score += 0.1; // Substantive response
        if (str_contains(strtolower($output), 'here') || str_contains(strtolower($output), 'sure')) $score += 0.1; // Helpful language
        
        // Negative signals
        if (str_contains(strtolower($output), 'error')) $score -= 0.3;
        if (str_contains(strtolower($output), "i can't") || str_contains(strtolower($output), "i cannot")) $score -= 0.2;
        if (str_contains(strtolower($output), 'sorry')) $score -= 0.1;

        return max(-1.0, min(1.0, $score));
    }
}
