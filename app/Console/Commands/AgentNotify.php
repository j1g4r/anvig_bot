<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\Notification\NotificationService;
use App\Models\NotificationPreference;

class AgentNotify extends Command
{
    protected $signature = 'agent:notify {user_id} {message} {--channel=}';
    protected $description = 'Send a test notification to a user';

    public function handle(NotificationService $service)
    {
        $userId = $this->argument('user_id');
        $message = $this->argument('message');
        $channel = $this->option('channel');

        $user = User::find($userId);
        if (!$user) {
            $this->error("User {$userId} not found.");
            return;
        }

        // Ensure preferences exist for test
        NotificationPreference::firstOrCreate(
            ['user_id' => $user->id, 'channel' => 'email'],
            ['enabled' => true, 'destination' => $user->email]
        );
        NotificationPreference::firstOrCreate(
            ['user_id' => $user->id, 'channel' => 'sms'],
            ['enabled' => true, 'destination' => '+15555555555']
        );

        $channels = $channel ? [$channel] : [];
        $this->info("Sending notification to User: {$user->name}...");
        
        $results = $service->notify($user, $message, $channels);

        foreach ($results as $ch => $status) {
            $this->line("CHANNEL [{$ch}]: " . ($status === 'sent' ? '✅' : '❌') . " {$status}");
        }
    }
}
