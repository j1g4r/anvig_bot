<?php

namespace App\Http\Controllers;

use App\Models\OllamaNode;
use App\Services\OllamaClusterService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OllamaClusterController extends Controller
{
    protected OllamaClusterService $clusterService;

    public function __construct(OllamaClusterService $clusterService)
    {
        $this->clusterService = $clusterService;
    }

    public function index()
    {
        $nodes = OllamaNode::where('user_id', auth()->id())
            ->orderBy('is_primary', 'desc')
            ->orderBy('status', 'asc')
            ->get();

        return Inertia::render('Ollama/Index', [
            'nodes' => $nodes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
        ]);

        $node = $this->clusterService->registerNode(
            auth()->id(),
            $validated['name'],
            $validated['host'],
            $validated['port']
        );

        return back()->with('flash', ['message' => "Node '{$node->name}' registered!"]);
    }

    public function update(Request $request, OllamaNode $node)
    {
        $this->ensureOwner($node);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'max_concurrent' => 'sometimes|integer|min:1|max:10',
            'is_primary' => 'sometimes|boolean',
        ]);

        if (isset($validated['is_primary']) && $validated['is_primary']) {
            OllamaNode::where('user_id', auth()->id())
                ->where('id', '!=', $node->id)
                ->update(['is_primary' => false]);
        }

        $node->update($validated);
        return back();
    }

    public function destroy(OllamaNode $node)
    {
        $this->ensureOwner($node);
        $node->delete();
        return back()->with('flash', ['message' => 'Node removed.']);
    }

    public function healthCheck(OllamaNode $node)
    {
        $this->ensureOwner($node);
        $result = $this->clusterService->healthCheck($node);
        return back();
    }

    public function healthCheckAll()
    {
        $this->clusterService->healthCheckAll(auth()->id());
        return back();
    }

    public function pullModel(Request $request, OllamaNode $node)
    {
        $this->ensureOwner($node);

        $validated = $request->validate(['model' => 'required|string']);

        $success = $this->clusterService->pullModel($node, $validated['model']);

        return response()->json(['success' => $success]);
    }

    private function ensureOwner(OllamaNode $node): void
    {
        if ($node->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
