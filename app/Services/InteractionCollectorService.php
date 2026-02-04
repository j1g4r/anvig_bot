<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Conversation;
use App\Models\LearningExample;
use Illuminate\Support\Facades\Log;

class InteractionCollectorService
{
    protected EmbeddingService $embeddingService;
    protected ContinuousLearningService $learningService;

    public function __construct()
    {
        $this->embeddingService = new EmbeddingService();
        $this->learningService = new ContinuousLearningService();
    }

    /**
     * Capture an interaction pair for learning.
     */
    public function capture(
        Conversation $conversation, 
        string $userInput, 
        string $agentOutput,
        ?int $messageId = null
    ): LearningExample {
        $agent = $conversation->agent;

        // Generate context embedding for similarity search
        $contextText = "User: {$userInput}\nAssistant: {$agentOutput}";
        $embedding = $this->embeddingService->getEmbedding($contextText);
        $binaryEmbedding = !empty($embedding) ? $this->embeddingService->packVector($embedding) : null;

        // Auto-score the interaction
        $autoScore = $this->learningService->scoreInteraction($userInput, $agentOutput, $agent);

        return LearningExample::create([
            'agent_id' => $agent->id,
            'conversation_id' => $conversation->id,
            'message_id' => $messageId,
            'user_input' => $userInput,
            'agent_output' => $agentOutput,
            'feedback_score' => $autoScore,
            'feedback_type' => 'implicit', // Will be updated to 'explicit' if user gives feedback
            'context_embedding' => $binaryEmbedding,
            'metadata' => [
                'auto_scored' => true,
                'model' => $agent->model ?? 'unknown',
            ],
        ]);
    }

    /**
     * Record explicit user feedback on an example.
     */
    public function recordFeedback(int $exampleId, float $score, ?string $note = null): void
    {
        $example = LearningExample::findOrFail($exampleId);
        
        $metadata = $example->metadata ?? [];
        $metadata['explicit_feedback'] = true;
        $metadata['feedback_note'] = $note;
        $metadata['feedback_at'] = now()->toIso8601String();

        $example->update([
            'feedback_score' => max(-1.0, min(1.0, $score)),
            'feedback_type' => 'explicit',
            'metadata' => $metadata,
        ]);

        Log::info("Recorded explicit feedback for example {$exampleId}: {$score}");
    }

    /**
     * Record feedback by message ID (for UI integration).
     */
    public function recordFeedbackByMessage(int $messageId, float $score, ?string $note = null): ?LearningExample
    {
        $example = LearningExample::where('message_id', $messageId)->first();
        
        if (!$example) {
            Log::warning("No learning example found for message {$messageId}");
            return null;
        }

        $this->recordFeedback($example->id, $score, $note);
        return $example->fresh();
    }

    /**
     * Auto-score based on tool execution results.
     */
    public function scoreToolExecution(
        Conversation $conversation, 
        string $toolName, 
        bool $success
    ): void {
        // Find the most recent example for this conversation
        $example = LearningExample::where('conversation_id', $conversation->id)
            ->latest()
            ->first();

        if (!$example) return;

        // Adjust score based on tool execution
        $adjustment = $success ? 0.2 : -0.3;
        $newScore = max(-1.0, min(1.0, $example->feedback_score + $adjustment));

        $metadata = $example->metadata ?? [];
        $metadata['tool_executions'] = $metadata['tool_executions'] ?? [];
        $metadata['tool_executions'][] = [
            'tool' => $toolName,
            'success' => $success,
            'at' => now()->toIso8601String(),
        ];

        $example->update([
            'feedback_score' => $newScore,
            'feedback_type' => 'tool_' . ($success ? 'success' : 'failure'),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Find similar past interactions for context.
     */
    public function findSimilar(string $query, Agent $agent, int $limit = 5): array
    {
        $queryEmbedding = $this->embeddingService->getEmbedding($query);
        
        if (empty($queryEmbedding)) {
            return [];
        }

        $examples = LearningExample::where('agent_id', $agent->id)
            ->whereNotNull('context_embedding')
            ->where('feedback_score', '>', 0) // Only positive examples
            ->get();

        $scored = [];
        foreach ($examples as $example) {
            $exampleEmbedding = $this->embeddingService->unpackVector($example->context_embedding);
            $similarity = $this->embeddingService->cosineSimilarity($queryEmbedding, $exampleEmbedding);
            $scored[] = [
                'example' => $example,
                'similarity' => $similarity,
            ];
        }

        // Sort by similarity and take top N
        usort($scored, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        
        return array_slice($scored, 0, $limit);
    }
}
