<?php

namespace App\Jobs\Middleware;

use Illuminate\Database\QueryException;

class SqliteLockRetry
{
    public function handle($job, $next): void
    {
        $attempts = 0;
        $maxAttempts = 5;
        
        while ($attempts < $maxAttempts) {
            try {
                $next($job);
                return;
            } catch (QueryException $e) {
                if (!str_contains($e->getMessage(), 'database is locked')) {
                    throw $e;
                }
                
                $attempts++;
                usleep(100000 * $attempts);
                
                if ($attempts >= $maxAttempts) {
                    throw $e;
                }
            }
        }
    }
}
