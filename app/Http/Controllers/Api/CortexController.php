<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CortexController extends Controller
{
    /**
     * Get real-time status for all agents.
     */
    public function agentStatus()
    {
        $agents = Agent::all()->map(function ($agent) {
            // Mock dynamic metrics (replace with Redis/DB metrics later)
            return [
                'id' => $agent->id,
                'name' => $agent->name,
                'status' => $this->mockStatus($agent->id),
                'metrics' => [
                    'cpu' => rand(10, 80),
                    'mem' => rand(20, 90),
                    'tasks' => rand(0, 5)
                ],
                'last_active' => now()->subMinutes(rand(0, 60))->toIso8601String()
            ];
        });

        return response()->json($agents);
    }

    /**
     * Get system-wide dashboard stats.
     */
    public function systemStats()
    {
        $totalAgents = Agent::count();
        $uptime = shell_exec('uptime -p') ?: 'up 1 hour'; // fallback

        return response()->json([
            'health' => 98,
            'tasksCompleted' => 1240, // Replace with Task::where('status','completed')->count()
            'totalTasks' => 1500,
            'uptime' => str_replace('up ', '', $uptime),
            'activeConnections' => rand(5, 15),
            'avgResponseTime' => rand(100, 300),
            'networkStatus' => 'HEALTHY'
        ]);
    }

    /**
     * Get historical efficiency data.
     */
    public function performanceHistory()
    {
        // Mock 24h history
        $history = [];
        for ($i = 0; $i < 24; $i++) {
            $history[] = [
                'hour' => $i,
                'efficiency' => rand(60, 99)
            ];
        }

        return response()->json($history);
    }

    private function mockStatus($id)
    {
        $statuses = ['active', 'idle', 'processing', 'active', 'active'];
        return $statuses[$id % count($statuses)];
    }

    /**
     * Get live task feed.
     */
    public function liveTasks()
    {
        try {
            $tasks = \App\Models\KanbanTask::with('agent')
                ->latest('updated_at')
                ->take(50)
                ->get()
                ->map(function ($task) {
                    $status = strtolower($task->status);
                    $progress = match($status) {
                        'completed', 'done' => 100,
                        'review', 'testing' => 80,
                        'in_progress', 'doing', 'processing' => 50,
                        'blocked' => 10,
                        default => 0
                    };

                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'agent' => $task->agent ? $task->agent->name : 'Unassigned',
                        'status' => $status,
                        // If priority is numeric, map to string, or use as is
                        'priority' => ucfirst($task->priority) ?: 'Medium',
                        'progress' => $progress,
                        'time' => $task->updated_at->diffForHumans(null, true, true)
                    ];
                });

            return response()->json($tasks);
        } catch (\Exception $e) {
            // Return empty list on error (e.g. table missing)
            return response()->json([]);
        }
    }
}
