<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\KanbanTask;
use App\Jobs\ProcessKanbanTask;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KanbanController extends Controller
{
    public function index()
    {
        return Inertia::render('Kanban', [
            'tasks' => KanbanTask::with('agent')->orderBy('order')->get(),
            'agents' => Agent::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'agent_id' => 'nullable|exists:agents,id',
            'priority' => 'required|in:low,medium,high',
        ]);

        $task = KanbanTask::create($validated);

        if ($task->agent_id) {
            ProcessKanbanTask::dispatch($task);
        }

        return back()->with('success', 'Task created and dispatched to agent.');
    }

    public function update(Request $request, KanbanTask $task)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:todo,in_progress,done,hold',
            'priority' => 'nullable|in:low,medium,high',
            'agent_id' => 'nullable|exists:agents,id',
            'order' => 'nullable|integer',
        ]);

        $task->update($validated);

        // Auto-dispatch if agent was just assigned or changed, and task is not done
        if ($request->has('agent_id') && $task->agent_id && $task->status !== 'done') {
            ProcessKanbanTask::dispatch($task);
        }

        return back()->with('success', 'Task updated successfully.');
    }

    public function destroy(KanbanTask $task)
    {
        $task->delete();
        return back()->with('success', 'Task deleted successfully.');
    }

    public function run(KanbanTask $task)
    {
        if (!$task->agent_id) {
            return back()->with('error', 'Please assign an agent first.');
        }

        ProcessKanbanTask::dispatch($task);

        return back()->with('success', 'Agent dispatched for mission.');
    }
}
