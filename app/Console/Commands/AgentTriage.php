<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TaskTriageService;

class AgentTriage extends Command
{
    protected $signature = 'agent:triage';
    protected $description = 'Jerry reviews the Hold column and assigns tasks.';

    public function handle(TaskTriageService $service)
    {
        $this->info("🕵️ Jerry is reviewing the Kanban Board...");
        $service->triage();
        $this->info("✅ Implementation assignments complete.");
    }
}
