<?php

namespace App\Services\Notification\Drivers;

use Illuminate\Support\Facades\Log;

class LogDriver implements NotificationDriverInterface
{
    public function send(string $destination, string $message): bool
    {
        Log::info("[NOTIFICATION::LOG] To: {$destination} | Message: {$message}");
        return true;
    }

    public function channel(): string
    {
        return 'log';
    }
}
