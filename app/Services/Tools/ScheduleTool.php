<?php

namespace App\Services\Tools;

use App\Models\ScheduledTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ScheduleTool implements ToolInterface
{
    public function name(): string
    {
        return 'schedule_mission';
    }

    public function description(): string
    {
        return 'Schedule a proactive background mission for yourself or another agent to run at a specific time in the future. Useful for checking status, periodic reports, or delayed actions.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'description' => 'The action to perform: "schedule" (default), "recurring", "list", "delete".',
                    'enum' => ['schedule', 'recurring', 'list', 'delete'],
                ],
                'mission' => [
                    'type' => 'string',
                    'description' => 'For "schedule": The instruction. For "recurring": The Artisan command (e.g. "agent:research").',
                ],
                'at' => [
                    'type' => 'string',
                    'description' => 'For "schedule": Time string. For "recurring": Cron expression (e.g. "0 * * * *").',
                ],
                'params' => [
                    'type' => 'string',
                    'description' => 'For "recurring": JSON string of command parameters (optional).',
                ],
                'job_id' => [
                    'type' => 'integer',
                    'description' => 'For "delete": The ID of the cron job to remove.',
                ],
                'target_agent_id' => [
                    'type' => 'integer',
                    'description' => 'Optional ID of agent.',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(array $input): string
    {
        $action = $input['action'] ?? 'schedule';
        $agentId = $input['target_agent_id'] ?? app('active_agent_id');

        try {
            // 1. Standard One-Time Mission
            if ($action === 'schedule') {
                if (empty($input['mission']) || empty($input['at'])) {
                    return "ERROR: 'mission' and 'at' are required for standard scheduling.";
                }
                $conversationId = app('active_conversation_id');
                if (!$conversationId) return "ERROR: No active conversation context.";

                $executeAt = Carbon::parse($input['at']);
                if ($executeAt->lt(now()->subSeconds(2))) {
                    return "ERROR: Cannot schedule in the past.";
                }

                ScheduledTask::create([
                    'conversation_id' => $conversationId,
                    'agent_id' => $agentId,
                    'mission_prompt' => $input['mission'],
                    'execute_at' => $executeAt,
                    'status' => 'pending',
                ]);

                return "SUCCESS: Mission scheduled for {$executeAt->diffForHumans()}.";
            }

            // 2. Create Recurring Cron Job
            if ($action === 'recurring') {
                if (empty($input['mission']) || empty($input['at'])) {
                    return "ERROR: 'mission' (command) and 'at' (cron expression) are required.";
                }
                
                $job = \App\Models\AgentCronJob::create([
                    'command' => $input['mission'],
                    'params' => json_decode($input['params'] ?? '{}', true),
                    'schedule_expression' => $input['at'],
                    'description' => "Created by Agent $agentId",
                    'creator_agent_id' => $agentId,
                    'is_active' => true,
                ]);

                return "SUCCESS: Recurring Job #{$job->id} created using '{$job->command}' on schedule '{$job->schedule_expression}'.";
            }

            // 3. List Jobs
            if ($action === 'list') {
                $jobs = \App\Models\AgentCronJob::all();
                if ($jobs->isEmpty()) return "No active cron jobs.";
                
                $out = "Active Cron Jobs:\n";
                foreach ($jobs as $job) {
                    $out .= "- [{$job->id}] {$job->command} ({$job->schedule_expression}) - Active: {$job->is_active}\n";
                }
                return $out;
            }

            // 4. Delete Job
            if ($action === 'delete') {
                if (empty($input['job_id'])) return "ERROR: 'job_id' required.";
                $job = \App\Models\AgentCronJob::find($input['job_id']);
                if (!$job) return "ERROR: Job not found.";
                
                $job->delete();
                return "SUCCESS: Job #{$input['job_id']} deleted.";
            }

            return "ERROR: Unknown action.";

        } catch (\Exception $e) {
            Log::error("ScheduleTool Error: " . $e->getMessage());
            return "ERROR: " . $e->getMessage();
        }
    }
}
