<?php

namespace App\Services\Tools;

use App\Models\Agent;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Notification\NotificationService;

class NotifyTool implements ContextAwareToolInterface
{
    protected NotificationService $notificationService;
    protected ?Conversation $conversation = null;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    public function setConversation(Conversation $conversation): void
    {
        $this->conversation = $conversation;
    }

    public function name(): string
    {
        return 'notify_user';
    }

    public function description(): string
    {
        return 'Send a notification to the user via their preferred channels (Email, SMS, etc.). Use this for important alerts, reminders, or updates that the user needs to see even if they are not active in the chat.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'message' => [
                    'type' => 'string',
                    'description' => 'The content of the notification.',
                ],
                'urgency' => [
                    'type' => 'string',
                    'enum' => ['low', 'medium', 'high'],
                    'description' => 'The urgency level of the notification.',
                ],
            ],
            'required' => ['message'],
        ];
    }

    public function execute(array $args): string
    {
        $message = $args['message'] ?? '';
        $urgency = $args['urgency'] ?? 'medium';

        // Get user from conversation context, or fallback to first user for safety
        $user = $this->conversation?->user ?? User::first();

        if (!$user) {
            return "Error: No user found to notify.";
        }

        $results = $this->notificationService->notify($user, "[{$urgency}] " . $message);

        $statusStrings = [];
        foreach ($results as $channel => $status) {
            $statusStrings[] = "{$channel}: {$status}";
        }

        return "Notification sent via: " . implode(', ', $statusStrings);
    }
}
