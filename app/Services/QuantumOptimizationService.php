<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class QuantumOptimizationService
{
    /**
     * Run the full optimization suite.
     */
    public function entangle(): array
    {
        $metrics = [];
        
        $metrics['database'] = $this->optimizeDatabase();
        $metrics['system'] = $this->optimizeSystem();
        $metrics['cleanup'] = $this->cleanupLogs();
        
        return $metrics;
    }

    private function optimizeDatabase(): array
    {
        // Simple optimization for MySQL/SQLite
        // In PostgreSQL this would be VACUUM
        
        $tables = ['messages', 'semantic_cache', 'agent_adaptations'];
        $driver = DB::getDriverName();
        $result = "No optimization needed for driver: $driver";
        
        try {
            if ($driver === 'mysql') {
                $tableList = implode(',', $tables);
                DB::statement("OPTIMIZE TABLE $tableList");
                $result = "Optimized tables: $tableList";
            } elseif ($driver === 'sqlite') {
                DB::statement("VACUUM");
                $result = "Ran VACUUM command";
            } elseif ($driver === 'pgsql') {
                 // Postgres requires running VACUUM outside transaction blocks usually, 
                 // but we can try basic analyze
                 DB::statement("ANALYZE");
                 $result = "Ran ANALYZE command";
            }
        } catch (\Exception $e) {
            $result = "Error: " . $e->getMessage();
        }

        return ['status' => 'success', 'message' => $result];
    }

    private function optimizeSystem(): array
    {
        try {
            // Note: In development these might be skipped or just simulated
            // Running optimize:clear is safer to avoid sticking bad config in dev
            if (app()->environment('production')) {
                Artisan::call('config:cache');
                Artisan::call('route:cache');
                Artisan::call('view:cache');
                $msg = "Caches primed (Config, Route, View)";
            } else {
                Artisan::call('optimize:clear');
                $msg = "Caches cleared for Development";
            }
        } catch (\Exception $e) {
             return ['status' => 'error', 'message' => $e->getMessage()];
        }

        return ['status' => 'success', 'message' => $msg];
    }

    private function cleanupLogs(): array
    {
        $days = 30;
        $date = now()->subDays($days);
        
        $deletedNotifications = 0;
        // Check if table exists before deleting
        if (DB::getSchemaBuilder()->hasTable('notification_logs')) {
             $deletedNotifications = DB::table('notification_logs')->where('created_at', '<', $date)->delete();
        }

        return [
            'status' => 'success',
            'message' => "Pruned $deletedNotifications old notification logs > $days days."
        ];
    }
}
