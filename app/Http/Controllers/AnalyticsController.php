<?php

namespace App\Http\Controllers;

use App\Models\Trace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function index()
    {
        return Inertia::render('Analytics/Index');
    }

    public function stats(Request $request)
    {
        $range = $request->input('range', '24h');
        
        $cutoff = match ($range) {
            '24h' => Carbon::now()->subDay(),
            '7d' => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            default => Carbon::now()->subDay(),
        };

        // 1. Fetch Stats from Traces (Agent Interaction) for legacy/specific breakdown
        $conversationTokens = Trace::where('created_at', '>=', $cutoff)->sum('tokens_total');
        
        // 2. Fetch Stats from Global API Logs (The new source of truth)
        $apiQuery = DB::table('api_logs')->where('created_at', '>=', $cutoff);
        
        $totals = [
            'total_tokens' => (int) $apiQuery->sum('tokens_total'),
            'total_input' => (int) $apiQuery->sum('tokens_input'),
            'total_output' => (int) $apiQuery->sum('tokens_output'),
            'total_cost_est' => (float) $apiQuery->sum('cost'), 
        ];

        // Fallback: If API logs are empty (migration just ran), use traces
        if ($totals['total_tokens'] === 0 && $conversationTokens > 0) {
             $totals['total_tokens'] = (int) Trace::where('created_at', '>=', $cutoff)->sum('tokens_total');
             $totals['total_input'] = (int) Trace::where('created_at', '>=', $cutoff)->sum('tokens_input');
             $totals['total_output'] = (int) Trace::where('created_at', '>=', $cutoff)->sum('tokens_output');
             // Approx cost
             $totals['total_cost_est'] = round(($totals['total_input'] * 0.00000015) + ($totals['total_output'] * 0.0000006), 6);
        }

        // 3. Usage by Model (New Chart)
        $byModel = DB::table('api_logs')
            ->select('model', DB::raw('SUM(tokens_total) as total'))
            ->where('created_at', '>=', $cutoff)
            ->groupBy('model')
            ->orderByDesc('total')
            ->get();

        // 4. Usage by Agent (Best effort link via metadata or traces)
        // We stick to Traces for Agent breakdown as api_logs might not always have agent_id easily accessible yet
        $byAgent = Trace::select('agent_id', DB::raw('SUM(tokens_total) as total'))
            ->where('created_at', '>=', $cutoff)
            ->whereNotNull('tokens_total')
            ->groupBy('agent_id')
            ->with('agent:id,name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'name' => $item->agent->name ?? 'Agent #' . $item->agent_id,
                'total' => (int) $item->total
            ]);

        // 5. Usage Over Time (Hourly buckets) - Merged view
        // Ideally we use api_logs for timeline, but let's use Traces for now as it maps to user activity better on the chart
        $traces = Trace::select(
            DB::raw("strftime('%Y-%m-%d %H:00:00', created_at) as hour"),
            DB::raw('SUM(tokens_total) as total')
        )
            ->where('created_at', '>=', $cutoff)
            ->whereNotNull('tokens_total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return response()->json([
            'totals' => $totals,
            'byAgent' => $byAgent,
            'byModel' => $byModel,
            'timeline' => $traces
        ]);
    }
}
