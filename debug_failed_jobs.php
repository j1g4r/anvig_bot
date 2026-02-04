<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$job = DB::table('failed_jobs')->orderBy('id', 'desc')->first();

if ($job) {
    echo "FAILED JOB ID: " . $job->id . "\n";
    echo "UUID: " . $job->uuid . "\n";
    echo "EXCEPTION:\n" . substr($job->exception, 0, 2000) . "\n..."; // Print first 2000 chars
} else {
    echo "No failed jobs found.\n";
}
