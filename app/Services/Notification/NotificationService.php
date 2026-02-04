<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Services\Notification\Drivers\NotificationDriverInterface;
use App\Services\Notification\Drivers\EmailDriver;
use App\Services\Notification\Drivers\LogDriver;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected array $drivers = [];

    public function __construct()
    {
        // Register Drivers
        $this->registerDriver('email', new EmailDriver());
        $this->registerDriver('log', new LogDriver());
        // Future: $this->registerDriver('sms', new TwilioDriver());
    }

    public function registerDriver(string $channel, NotificationDriverInterface $driver): void
    {
        $this->drivers[$channel] = $driver;
    }

    /**
     * Send a notification to a user via their preferred channels.
     *
     * @param User $user
     * @param string $message
     * @param array $channels Optional specific channels to force (if enabled)
     * @return array Status of each channel attempt
     */
    public function notify(User $user, string $message, array $channels = []): array
    {
        // 1. Get User Preferences
        $preferences = NotificationPreference::where('user_id', $user->id)
            ->where('enabled', true)
            ->get();

        if ($preferences->isEmpty()) {
            return ['status' => 'skipped', 'reason' => 'no_enabled_channels'];
        }

        $results = [];

        foreach ($preferences as $pref) {
            // Apply channel filtering if specific channels requested
            if (!empty($channels) && !in_array($pref->channel, $channels)) {
                continue;
            }

            $driverName = $this->resolveDriverName($pref->channel);
            
            if (!isset($this->drivers[$driverName])) {
                Log::warning("No driver found for channel: {$pref->channel}");
                continue;
            }

            $driver = $this->drivers[$driverName];
            $destination = $pref->destination ?? $user->email; // Default fallback to user email

            try {
                $success = $driver->send($destination, $message);
                $status = $success ? 'sent' : 'failed';
                $error = null;
            } catch (\Exception $e) {
                $status = 'failed';
                $error = $e->getMessage();
                $success = false;
            }

            // Log result
            NotificationLog::create([
                'user_id' => $user->id,
                'channel' => $pref->channel,
                'content' => $message,
                'status' => $status,
                'error' => $error,
            ]);

            $results[$pref->channel] = $status;
        }

        return $results;
    }

    protected function resolveDriverName(string $channel): string
    {
        // Map abstract channels to concrete drivers
        // For local dev, we might map 'sms' to 'log' if no SMS driver exists
        return match ($channel) {
            'email' => 'email',
            'sms', 'whatsapp' => 'log', // Fallback to log for now until Twilio is implemented
            default => 'log',
        };
    }
}
