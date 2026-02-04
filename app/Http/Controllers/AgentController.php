<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AgentController extends Controller
{
    public function index()
    {
        return Inertia::render('Agent/Index', [
            'agents' => Agent::withCount('conversations')->get(),
        ]);
    }

    public function show(Agent $agent)
    {
        // Get or create active conversation
        $conversation = $agent->conversations()->latest()->first();
        
        if (!$conversation) {
            $conversation = Conversation::create([
                'agent_id' => $agent->id,
                'title' => 'New Conversation',
                'status' => 'active'
            ]);
        }

        return Inertia::render('Agent/Chat', [
            'agent' => $agent,
            'conversation' => $conversation->load(['messages', 'agent', 'canvas', 'participants.agent']),
            'allAgents' => Agent::where('id', '!=', $agent->id)->get(),
        ]);
    }

    public function chat(Request $request, Agent $agent, Conversation $conversation)
    {
        $request->validate([
            'message' => 'required|string',
            'image' => 'nullable|image|max:10240', // 10MB max
        ]);

        $images = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('chat-images', 'public');
            $images = [$path];
        }

        // Analyze Sentiment
        $sentimentService = new \App\Services\SentimentAnalysisService();
        $sentimentResult = $sentimentService->analyze($request->message);

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $request->message,
            'images' => $images,
            'sentiment' => $sentimentResult['sentiment'],
            'sentiment_score' => $sentimentResult['score'],
        ]);

        // Async Process
        \App\Jobs\ProcessAgentThought::dispatch($conversation);

        return back();
    }

    public function memories()
    {
        $memories = \Illuminate\Support\Facades\DB::table('memories')
            ->select('id', 'agent_id', 'content', 'metadata', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return Inertia::render('Agent/MemoryExplorer', [
            'memories' => $memories,
        ]);
    }

    public function cortex()
    {
        return Inertia::render('Cortex/Index', [
            'agents' => Agent::all(),
            'conversations' => Conversation::with(['agent', 'participants.agent'])->where('status', 'active')->get(),
        ]);
    }

    public function export(Agent $agent, Conversation $conversation)
    {
        $data = [
            'agent' => [
                'name' => $agent->name,
                'model' => $agent->model,
            ],
            'conversation' => [
                'title' => $conversation->title,
                'started_at' => $conversation->created_at,
            ],
            'messages' => $conversation->messages()->orderBy('id')->get()->map(function ($m) {
                return [
                    'role' => $m->role,
                    'content' => $m->content,
                    'timestamp' => $m->created_at,
                    'tool_calls' => $m->tool_calls,
                ];
            }),
        ];

        $filename = "jerry_export_" . $conversation->id . "_" . date('Y-m-d') . ".json";
        
        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    public function update(Request $request, Agent $agent)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'personality' => 'nullable|string',
            'system_prompt' => 'nullable|string',
        ]);

        $agent->update($validated);

        return back()->with('success', 'Agent updated successfully.');
    }

    /**
     * Add an agent as participant to conversation.
     */
    public function addParticipant(Request $request, Conversation $conversation)
    {
        $request->validate(['agent_id' => 'required|exists:agents,id']);

        $agent = Agent::find($request->agent_id);
        $existing = $conversation->participants()->where('agent_id', $agent->id)->first();

        if (!$existing) {
            $conversation->addParticipant($agent);
            $conversation->update(['is_multi_agent' => true]);
        }

        return back()->with('flash', ['message' => "{$agent->name} joined the conversation!"]);
    }

    /**
     * Remove an agent participant from conversation.
     */
    public function removeParticipant(Request $request, Conversation $conversation)
    {
        $request->validate(['agent_id' => 'required|exists:agents,id']);

        $agent = Agent::find($request->agent_id);
        $conversation->removeParticipant($agent);

        if ($conversation->participants()->count() === 0) {
            $conversation->update(['is_multi_agent' => false]);
        }

        return back()->with('flash', ['message' => "{$agent->name} left the conversation."]);
    }
}
