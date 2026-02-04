<?php

namespace App\Services\Tools;

use App\Models\Agent;
use App\Services\ContinuousLearningService;
use App\Services\InteractionCollectorService;

class LearningTool implements ToolInterface
{
    protected ?int $agentId = null;

    public function name(): string
    {
        return 'learning';
    }

    public function description(): string
    {
        return 'Self-reflection and continuous learning tool. Analyze your own performance, review patterns from past interactions, and improve based on user feedback.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['reflect', 'insights', 'recall_success', 'find_similar'],
                    'description' => 'Action to perform: reflect (self-analyze), insights (get performance stats), recall_success (retrieve successful patterns), find_similar (find similar past interactions)',
                ],
                'query' => [
                    'type' => 'string',
                    'description' => 'Query for find_similar action - the current user request to find similar past interactions for',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function setConversation($conversation): void
    {
        $this->agentId = $conversation->agent_id;
    }

    public function execute(array $args): string
    {
        $action = $args['action'] ?? 'insights';
        
        if (!$this->agentId) {
            return 'Error: No agent context available.';
        }

        $agent = Agent::find($this->agentId);
        if (!$agent) {
            return 'Error: Agent not found.';
        }

        $learningService = new ContinuousLearningService();
        $collectorService = new InteractionCollectorService();

        return match ($action) {
            'reflect' => $this->reflect($agent, $learningService),
            'insights' => $this->getInsights($agent, $learningService),
            'recall_success' => $this->recallSuccess($agent),
            'find_similar' => $this->findSimilar($agent, $collectorService, $args['query'] ?? ''),
            default => 'Unknown action: ' . $action,
        };
    }

    protected function reflect(Agent $agent, ContinuousLearningService $service): string
    {
        $insights = $service->getInsights($agent);
        
        $reflection = "## Self-Reflection Analysis\n\n";
        $reflection .= "### Performance Overview\n";
        $reflection .= "- **Total Interactions Recorded**: {$insights['total_examples']}\n";
        $reflection .= "- **User Satisfaction Rate**: {$insights['satisfaction_rate']}%\n";
        $reflection .= "- **Active Learned Behaviors**: {$insights['active_adaptations']}\n\n";
        
        $reflection .= "### Trend Analysis\n";
        $trend = $insights['trend'];
        $reflection .= "- Recent avg score: {$trend['recent_avg']}\n";
        $reflection .= "- Previous avg score: {$trend['previous_avg']}\n";
        $reflection .= "- Direction: **{$trend['direction']}**\n\n";

        if ($insights['satisfaction_rate'] < 70) {
            $reflection .= "> ⚠️ **Improvement Needed**: Satisfaction rate is below 70%. Consider reviewing negative feedback patterns.\n";
        } elseif ($insights['satisfaction_rate'] >= 90) {
            $reflection .= "> ✅ **Excellent Performance**: Maintaining high user satisfaction.\n";
        }

        return $reflection;
    }

    protected function getInsights(Agent $agent, ContinuousLearningService $service): string
    {
        $insights = $service->getInsights($agent);
        
        return json_encode([
            'status' => 'success',
            'insights' => $insights,
        ], JSON_PRETTY_PRINT);
    }

    protected function recallSuccess(Agent $agent): string
    {
        $examples = \App\Models\LearningExample::where('agent_id', $agent->id)
            ->positive()
            ->orderByDesc('feedback_score')
            ->limit(5)
            ->get();

        if ($examples->isEmpty()) {
            return 'No successful interactions recorded yet.';
        }

        $result = "## Top Successful Interactions\n\n";
        foreach ($examples as $i => $example) {
            $num = $i + 1;
            $score = round($example->feedback_score, 2);
            $result .= "### #{$num} (Score: {$score})\n";
            $result .= "**User**: " . substr($example->user_input, 0, 100) . "...\n";
            $result .= "**Response Pattern**: " . substr($example->agent_output, 0, 150) . "...\n\n";
        }

        return $result;
    }

    protected function findSimilar(Agent $agent, InteractionCollectorService $service, string $query): string
    {
        if (empty($query)) {
            return 'Error: Query is required for find_similar action.';
        }

        $similar = $service->findSimilar($query, $agent, 3);
        
        if (empty($similar)) {
            return 'No similar past interactions found.';
        }

        $result = "## Similar Past Interactions\n\n";
        foreach ($similar as $i => $item) {
            $num = $i + 1;
            $example = $item['example'];
            $similarity = round($item['similarity'] * 100, 1);
            
            $result .= "### #{$num} (Similarity: {$similarity}%)\n";
            $result .= "**Past User Input**: {$example->user_input}\n";
            $result .= "**Successful Response**: " . substr($example->agent_output, 0, 200) . "...\n";
            $result .= "**Feedback Score**: {$example->feedback_score}\n\n";
        }

        return $result;
    }
}
