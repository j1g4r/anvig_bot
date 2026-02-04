<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\LearningExample;
use App\Models\LearningSession;
use App\Models\AgentAdaptation;
use App\Services\ContinuousLearningService;
use App\Services\InteractionCollectorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningController extends Controller
{
    /**
     * List learning examples for an agent.
     */
    public function examples(Request $request): JsonResponse
    {
        $request->validate([
            'agent_id' => 'nullable|exists:agents,id',
            'feedback_type' => 'nullable|in:positive,negative,neutral,explicit',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = LearningExample::query()->with('agent');

        if ($request->agent_id) {
            $query->where('agent_id', $request->agent_id);
        }

        if ($request->feedback_type) {
            match ($request->feedback_type) {
                'positive' => $query->where('feedback_score', '>', 0),
                'negative' => $query->where('feedback_score', '<', 0),
                'neutral' => $query->where('feedback_score', 0),
                'explicit' => $query->where('feedback_type', 'explicit'),
            };
        }

        $examples = $query->orderByDesc('created_at')
            ->limit($request->limit ?? 50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $examples,
        ]);
    }

    /**
     * Submit feedback for a message.
     */
    public function submitFeedback(Request $request): JsonResponse
    {
        $request->validate([
            'message_id' => 'required|integer',
            'score' => 'required|numeric|min:-1|max:1',
            'note' => 'nullable|string|max:500',
        ]);

        $collector = new InteractionCollectorService();
        $example = $collector->recordFeedbackByMessage(
            $request->message_id,
            $request->score,
            $request->note
        );

        if (!$example) {
            // No existing example - create one from message
            $message = \App\Models\Message::find($request->message_id);
            if (!$message || $message->role !== 'assistant') {
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found or not an assistant message',
                ], 404);
            }

            // Find previous user message
            $conversation = $message->conversation;
            $userMessage = $conversation->messages()
                ->where('id', '<', $message->id)
                ->where('role', 'user')
                ->orderByDesc('id')
                ->first();

            if ($userMessage) {
                $example = $collector->capture(
                    $conversation,
                    $userMessage->content,
                    $message->content,
                    $message->id
                );
                $collector->recordFeedback($example->id, $request->score, $request->note);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Feedback recorded',
            'data' => $example,
        ]);
    }

    /**
     * Get learning insights for an agent.
     */
    public function insights(int $agentId): JsonResponse
    {
        $agent = Agent::find($agentId);
        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Agent not found',
            ], 404);
        }

        $service = new ContinuousLearningService();
        $insights = $service->getInsights($agent);

        return response()->json([
            'success' => true,
            'data' => $insights,
        ]);
    }

    /**
     * Trigger a learning session for an agent.
     */
    public function train(Request $request, int $agentId): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:10|max:500',
        ]);

        $agent = Agent::find($agentId);
        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Agent not found',
            ], 404);
        }

        $service = new ContinuousLearningService();
        $session = $service->learn($agent, $request->limit ?? 100);

        return response()->json([
            'success' => true,
            'message' => 'Learning session ' . ($session->isCompleted() ? 'completed' : $session->status),
            'data' => $session,
        ]);
    }

    /**
     * List training sessions for an agent.
     */
    public function sessions(int $agentId): JsonResponse
    {
        $sessions = LearningSession::where('agent_id', $agentId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * List and manage adaptations for an agent.
     */
    public function adaptations(int $agentId): JsonResponse
    {
        $adaptations = AgentAdaptation::where('agent_id', $agentId)
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $adaptations,
        ]);
    }

    /**
     * Toggle adaptation active status.
     */
    public function toggleAdaptation(int $id): JsonResponse
    {
        $adaptation = AgentAdaptation::find($id);
        if (!$adaptation) {
            return response()->json([
                'success' => false,
                'message' => 'Adaptation not found',
            ], 404);
        }

        $adaptation->update(['active' => !$adaptation->active]);

        return response()->json([
            'success' => true,
            'message' => 'Adaptation ' . ($adaptation->active ? 'activated' : 'deactivated'),
            'data' => $adaptation,
        ]);
    }

    /**
     * Delete an adaptation.
     */
    public function deleteAdaptation(int $id): JsonResponse
    {
        $adaptation = AgentAdaptation::find($id);
        if (!$adaptation) {
            return response()->json([
                'success' => false,
                'message' => 'Adaptation not found',
            ], 404);
        }

        $adaptation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Adaptation deleted',
        ]);
    }
}
