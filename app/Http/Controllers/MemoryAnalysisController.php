<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Services\EmbeddingService;
use Illuminate\Support\Facades\Process;

class MemoryAnalysisController extends Controller
{
    public function index(EmbeddingService $embedder)
    {
        // 1. Fetch Memories
        $memories = DB::table('memories')
            ->select('id', 'content', 'embedding_binary', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(300) // Limit for performance
            ->get();

        // 2. Prepare Data for Python
        $payload = $memories->map(function ($m) use ($embedder) {
            return [
                'id' => $m->id,
                'content' => \Illuminate\Support\Str::limit($m->content, 100),
                'created_at' => $m->created_at,
                'embedding' => $embedder->unpackVector($m->embedding_binary)
            ];
        })->toArray();

        // 3. Call Python Script
        $scriptPath = base_path('scripts/memory_cluster.py');
        $pythonPath = base_path('.venv/bin/python'); // Use venv
        
        // Pass data via stdin
        $process = Process::input(json_encode($payload))
            ->run([$pythonPath, $scriptPath]);

        if ($process->failed()) {
            return Inertia::render('Memory/Galaxy', [
                'error' => $process->errorOutput(),
                'points' => []
            ]);
        }

        $points = json_decode($process->output(), true);

        return Inertia::render('Memory/Galaxy', [
            'points' => $points ?? []
        ]);
    }
}
