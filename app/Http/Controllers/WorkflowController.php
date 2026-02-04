<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Models\WorkflowNode;
use App\Models\WorkflowEdge;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WorkflowController extends Controller
{
    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    public function index()
    {
        $workflows = Workflow::where('user_id', Auth::id())
            ->withCount('nodes')
            ->orderBy('updated_at', 'desc')
            ->get();

        return Inertia::render('Workflows/Index', [
            'workflows' => $workflows,
        ]);
    }

    public function create()
    {
        return Inertia::render('Workflows/Editor', [
            'workflow' => null,
            'nodes' => [],
            'edges' => [],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nodes' => 'required|array',
            'edges' => 'array',
        ]);

        $workflow = Workflow::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'description' => $request->description,
            'canvas_data' => $request->canvas_data,
        ]);

        $this->saveNodesAndEdges($workflow, $request->nodes, $request->edges ?? []);

        return redirect()->route('workflows.index')->with('flash', [
            'message' => "Workflow '{$workflow->name}' created!",
        ]);
    }

    public function edit(Workflow $workflow)
    {
        if ($workflow->user_id !== Auth::id()) {
            abort(403);
        }

        return Inertia::render('Workflows/Editor', [
            'workflow' => $workflow,
            'nodes' => $workflow->nodes,
            'edges' => $workflow->edges,
        ]);
    }

    public function update(Request $request, Workflow $workflow)
    {
        if ($workflow->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'nodes' => 'required|array',
            'edges' => 'array',
        ]);

        $workflow->update([
            'name' => $request->name,
            'description' => $request->description,
            'canvas_data' => $request->canvas_data,
        ]);

        // Clear and re-save nodes/edges
        $workflow->nodes()->delete();
        $workflow->edges()->delete();
        $this->saveNodesAndEdges($workflow, $request->nodes, $request->edges ?? []);

        return redirect()->route('workflows.index')->with('flash', [
            'message' => "Workflow updated!",
        ]);
    }

    public function run(Workflow $workflow)
    {
        if ($workflow->user_id !== Auth::id()) {
            abort(403);
        }

        $run = $this->workflowService->execute($workflow);

        return response()->json([
            'run_id' => $run->id,
            'status' => $run->status,
            'logs' => $run->logs,
        ]);
    }

    public function destroy(Workflow $workflow)
    {
        if ($workflow->user_id !== Auth::id()) {
            abort(403);
        }

        $name = $workflow->name;
        $workflow->delete();

        return back()->with('flash', [
            'message' => "Workflow '{$name}' deleted.",
        ]);
    }

    protected function saveNodesAndEdges(Workflow $workflow, array $nodes, array $edges): void
    {
        foreach ($nodes as $node) {
            WorkflowNode::create([
                'workflow_id' => $workflow->id,
                'node_id' => $node['id'],
                'type' => $node['type'],
                'action_type' => $node['action_type'],
                'config' => $node['config'] ?? [],
                'position' => $node['position'] ?? ['x' => 0, 'y' => 0],
            ]);
        }

        foreach ($edges as $edge) {
            WorkflowEdge::create([
                'workflow_id' => $workflow->id,
                'source_node_id' => $edge['source'],
                'target_node_id' => $edge['target'],
                'source_handle' => $edge['sourceHandle'] ?? null,
            ]);
        }
    }
}
