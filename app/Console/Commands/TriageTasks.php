<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TaskTriageService;

class TriageTasks extends Command
{
    protected $signature = 'agent:triage';
    protected $description = 'Trigger Jerry to triage and assign tasks from the backlog';

    public function handle(TaskTriageService $triageService)
    {
        $this->info('Starting Task Triage...');
        $triageService->triage();
        $this->info('Triage Complete.');
    }
}
