<?php

namespace App\Services\Tools;

use App\Models\KanbanTask;

class KanbanTool implements ToolInterface
{
    public function name(): string
    {
        return 'kanban_board';
    }

    public function description(): string
    {
        return 'Manage the visual Kanban board. Use this to update task status, create new follow-up tasks, or assign tasks to agents.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['create_task', 'update_status', 'assign_agent'],
                    'description' => 'The action to perform on the board.',
                ],
                'task_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the task to update (required for update/assign).',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Title of the new task.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Description of the task.',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['todo', 'in_progress', 'done'],
                    'description' => 'New status for the task.',
                ],
                'agent_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the agent to assign.',
                ],
                'priority' => [
                    'type' => 'string',
                    'enum' => ['low', 'medium', 'high'],
                    'description' => 'Priority level.',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(array $input): string
    {
        try {
            // Robustness: Unwrap 'params'
            $args = array_merge($input, $input['params'] ?? []);
            $action = $args['action'] ?? '';

            switch ($action) {
                case 'create_task':
                    $task = KanbanTask::create([
                        'title' => $args['title'] ?? 'Untitled Task',
                        'description' => $args['description'] ?? '',
                        'status' => $args['status'] ?? 'todo',
                        'priority' => $args['priority'] ?? 'medium',
                        'agent_id' => $args['agent_id'] ?? null,
                    ]);
                    return "Task #{$task->id} [{$task->title}] created on the board.";

                case 'update_status':
                    $task = KanbanTask::findOrFail($args['task_id']);
                    $task->update(['status' => $args['status']]);
                    
                    if ($args['status'] === 'done') {
                        (new \App\Services\TaskTriageService())->triage();
                    }

                    return "Task #{$task->id} moved to [{$args['status']}].";

                case 'assign_agent':
                    $task = KanbanTask::findOrFail($args['task_id']);
                    $task->update(['agent_id' => $args['agent_id']]);
                    return "Task #{$task->id} assigned to Agent ID: " . $args['agent_id'];

                // Add missing 'view' action that agents are trying to use
                case 'view':
                case 'list':
                    $tasks = KanbanTask::where('status', '!=', 'done')->get();
                    $output = "Active Kanban Tasks:\n";
                    foreach ($tasks as $t) {
                        $output .= "#{$t->id} [{$t->status}] {$t->title} (Agent: {$t->agent_id})\n";
                    }
                    return $output;

                default:
                    return "Invalid action: $action. Valid actions: create_task, update_status, assign_agent, view.";
            }
        } catch (\Exception $e) {
            return "Kanban Error: " . $e->getMessage();
        }
    }
}
