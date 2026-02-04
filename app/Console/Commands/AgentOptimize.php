<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QuantumOptimizationService;

class AgentOptimize extends Command
{
    protected $signature = 'agent:optimize';
    protected $description = 'Perform system-wide optimization and cleanup.';

    public function handle(QuantumOptimizationService $service)
    {
        $this->info("🌌 Initiating Quantum Entanglement (System Optimization)...");
        
        $metrics = $service->entangle();
        
        $this->info("------------------------------------------------");
        
        // Database
        $this->info("💾 Database:");
        $this->line("   " . $metrics['database']['message']);
        
        // System
        $this->info("⚙️  System:");
        $this->line("   " . $metrics['system']['message']);
        
        // Cleanup
        $this->info("🧹 Cleanup:");
        $this->line("   " . $metrics['cleanup']['message']);
        
        $this->info("------------------------------------------------");
        $this->info("✨ System is now maintaining Peak Entropy.");
    }
}
