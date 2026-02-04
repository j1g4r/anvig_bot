<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPattern;
use Carbon\Carbon;

class PredictionService
{
    /**
     * Train the model with a new interaction.
     */
    public function train(User $user, string $actionType, ?Carbon $timestamp = null): void
    {
        $time = $timestamp ?? now();
        $isWeekend = $time->isWeekend();
        $hour = $time->format('H:00');
        
        // Context Key: '09:00|weekday'
        $contextKey = "{$hour}|" . ($isWeekend ? 'weekend' : 'weekday');
        
        $pattern = UserPattern::firstOrNew([
            'user_id' => $user->id,
            'action_type' => $actionType,
            'context_key' => $contextKey,
        ]);
        
        $pattern->frequency = ($pattern->frequency ?? 0) + 1;
        $pattern->last_occurrence_at = $time;
        
        // Simplified Confidence: Capped at 0.95, grows with frequency
        // Logic: 1 -> 0.1, 5 -> 0.5, 10 -> 0.9
        $pattern->confidence = min(0.95, $pattern->frequency * 0.1);
        
        $pattern->save();
    }

    /**
     * Predict probable next actions based on current context.
     */
    public function predict(User $user): array
    {
        $now = now();
        $isWeekend = $now->isWeekend();
        $hour = $now->format('H:00');
        $contextKey = "{$hour}|" . ($isWeekend ? 'weekend' : 'weekday');
        
        // Find patterns for this context with high confidence (> 0.4)
        return UserPattern::where('user_id', $user->id)
            ->where('context_key', $contextKey)
            ->where('confidence', '>=', 0.4)
            ->orderByDesc('confidence')
            ->get()
            ->map(function ($p) {
                return [
                    'action' => $p->action_type,
                    'confidence' => (float) $p->confidence,
                    'message' => $this->getPredictionMessage($p->action_type),
                ];
            })
            ->toArray();
    }

    private function getPredictionMessage(string $action): string
    {
        return match ($action) {
            'check_status' => 'Check system status?',
            'run_report' => 'Run the daily report?',
            'check_emails' => 'Check unread emails?',
            'backup_db' => 'Run database backup?',
            default => "Perform $action?",
        };
    }
}
