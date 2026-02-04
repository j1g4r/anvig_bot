<?php

namespace App\Services;

use App\Services\Tools\RoadmapTool;
use Illuminate\Support\Facades\Log;

class EvolutionService
{
    protected RoadmapTool $roadmapTool;

    public function __construct()
    {
        $this->roadmapTool = new RoadmapTool();
    }

    /**
     * Identify the current evolution cycle and propose a plan.
     */
    public function getNextEvolutionStep(): array
    {
        $roadmapContent = $this->roadmapTool->execute(['action' => 'read']);
        
        // Simple parsing logic
        // Find first incomplete task that looks like a Cycle "**C\d+**"
        
        $lines = explode("\n", $roadmapContent);
        $nextCycle = null;
        
        foreach ($lines as $line) {
            if (preg_match('/\[ \]\s+\*\*(C\d+)\*\*:\s+(.*)/', $line, $matches)) {
                $nextCycle = [
                    'id' => $matches[1],
                    'description' => $matches[2],
                ];
                break;
            }
        }

        if (!$nextCycle) {
            return ['status' => 'complete', 'message' => 'No pending cycles found.'];
        }

        return [
            'status' => 'pending',
            'cycle' => $nextCycle,
            'prompt' => "You are the Autonomous Architect. The next cycle is {$nextCycle['id']}: {$nextCycle['description']}. Analyze this goal and create a scaffolding plan. Use the 'file_manager' tool to create initial directories or files if needed."
        ];
    }
}
