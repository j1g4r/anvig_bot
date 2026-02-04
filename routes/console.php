<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
Schedule::command('agent:poke')->everyTenMinutes();

Schedule::call(function () {
    $tasks = \App\Models\ScheduledTask::where('status', 'pending')
        ->where('execute_at', '<=', now())
        ->get();

    foreach ($tasks as $task) {
        \App\Jobs\ProcessScheduledMission::dispatch($task->id);
    }
})->everyMinute();

// Era 5: Autonomous Schedules
Schedule::command('agent:learn')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('agent:triage')->everyFiveMinutes(); // Jerry checks tasks in the morning
Schedule::command('agent:federate')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('agent:evolve')->weeklyOn(1, '06:00');
Schedule::command('agent:research')->weeklyOn(5, '20:00'); // Fridays
Schedule::command('agent:optimize')->weeklyOn(0, '04:00'); // Sundays
Schedule::command('agent:ensure-active')->everyMinute();
Schedule::command('monitoring:prune')->daily()->at('01:00'); // Daily cleanup

// Dynamic Agent Cron Jobs
try {
    if (Illuminate\Support\Facades\Schema::hasTable('agent_cron_jobs')) {
        $jobs = \App\Models\AgentCronJob::active()->get();
        foreach ($jobs as $job) {
            Schedule::command($job->command, $job->params ?? [])
                ->cron($job->schedule_expression)
                ->description($job->description ?? 'Dynamic Agent Task');
        }
    }
} catch (\Exception $e) {
    // Fail silently if DB not ready (e.g. during migration)
}
