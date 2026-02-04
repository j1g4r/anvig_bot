<?php

namespace App\Services\Notification\Drivers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailDriver implements NotificationDriverInterface
{
    public function send(string $destination, string $message): bool
    {
        try {
            // Using raw email for simplicity now, can be upgraded to Mailable later
            Mail::raw($message, function ($mail) use ($destination) {
                $mail->to($destination)
                     ->subject('New Notification from Agent');
            });
            return true;
        } catch (\Exception $e) {
            Log::error("Email notification failed: " . $e->getMessage());
            return false;
        }
    }

    public function channel(): string
    {
        return 'email';
    }
}
