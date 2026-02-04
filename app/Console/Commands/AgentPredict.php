<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PredictionService;
use App\Models\User;

class AgentPredict extends Command
{
    protected $signature = 'agent:predict {user_id?}';
    protected $description = 'Simulate user behavior training and predict next actions.';

    public function handle(PredictionService $service)
    {
        $userId = $this->argument('user_id') ?? User::first()->id ?? 1;
        $user = User::find($userId);

        if (!$user) {
            $this->error("User not found.");
            return;
        }

        $this->info("🔮 Training Predictive Model for User: {$user->name}");

        // Simulate training: 5 times "check_status" at current hour
        $action = 'check_status';
        for ($i = 0; $i < 5; $i++) {
            $service->train($user, $action);
            $this->line("   Log: User performed '$action' (Frequency: " . ($i+1) . ")");
        }

        $this->newLine();
        $this->info("🧠 Generating Predictions for CURRENT context...");
        
        $predictions = $service->predict($user);

        if (empty($predictions)) {
            $this->warn("   No high-confidence predictions found.");
        } else {
            foreach ($predictions as $pred) {
                $pct = $pred['confidence'] * 100;
                $this->info("   ✨ Suggestion: \"{$pred['message']}\" (Confidence: {$pct}%)");
            }
        }
    }
}
