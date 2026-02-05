<?php

namespace App\Http\Controllers\Trading;

use App\Models\Trade;
use App\Models\TradingStrategy;
use App\Services\Trading\PortfolioAnalyticsService;
use App\Services\Trading\TradeExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TradingController extends Controller
{
    public function __construct(
        private TradeExecutionService $execution,
        private PortfolioAnalyticsService $analytics
    ) {}

    public function portfolio(Request $request): JsonResponse
    {
        $paper = $request->boolean('paper', true);
        $snapshot = $this->execution->getPortfolioSnapshot($paper);
        
        return response()->json([
            'mode' => $paper ? 'paper' : 'live',
            'balance' => $snapshot['balance'] ?? 10000.00,
            'positions' => $snapshot['positions'] ?? [],
            'allocation' => $snapshot['allocation'] ?? [],
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'paper' => $this->analytics->getPerformance(true),
            'live' => $this->analytics->getPerformance(false),
        ]);
    }

    public function trades(Request $request): JsonResponse
    {
        $paper = $request->boolean('paper', true);
        $limit = $request->integer('limit', 50);
        
        $trades = Trade::where('paper_trade', $paper)
            ->orderByDesc('opened_at')
            ->limit($limit)
            ->get();
        
        return response()->json($trades);
    }

    public function closeTrade(string $id): JsonResponse
    {
        $trade = Trade::findOrFail($id);
        $result = $this->execution->closeTrade($trade);
        
        return response()->json(['success' => $result, 'trade' => $trade->fresh()]);
    }

    public function strategies(): JsonResponse
    {
        return response()->json(
            TradingStrategy::with('skills')->get()
        );
    }

    public function toggleStrategy(string $id): JsonResponse
    {
        $strategy = TradingStrategy::findOrFail($id);
        $strategy->update(['enabled' => !$strategy->enabled]);
        
        return response()->json(['enabled' => $strategy->enabled]);
    }

    public function runStrategies(Request $request): JsonResponse
    {
        $paper = $request->boolean('paper', true);
        $pairs = $request->input('pairs', []);
        
        // Dispatch execution without blocking request
        // In real implementation, this would queue a job
        
        return response()->json([
            'status' => 'dispatched',
            'paper' => $paper,
            'pairs' => $pairs,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
