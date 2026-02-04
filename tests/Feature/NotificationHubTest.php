<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Conversation;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Notification\NotificationService;
use App\Services\Tools\NotifyTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_save_notification_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('notifications.update'), [
            'channels' => [
                'email' => ['enabled' => true, 'destination' => 'test@example.com'],
                'sms' => ['enabled' => false, 'destination' => '+123456789'],
            ]
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'channel' => 'email',
            'enabled' => true,
            'destination' => 'test@example.com',
        ]);
        
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'channel' => 'sms',
            'enabled' => false,
        ]);
    }

    public function test_notification_service_sends_via_enabled_channel()
    {
        Mail::fake();
        
        $user = User::factory()->create(['email' => 'user@example.com']);
        NotificationPreference::create([
            'user_id' => $user->id,
            'channel' => 'email',
            'enabled' => true,
            'destination' => 'custom@example.com',
        ]);

        $service = new NotificationService();
        $results = $service->notify($user, "Test Message");

        $this->assertEquals('sent', $results['email']);
        
        // Assert log entry created
        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $user->id,
            'channel' => 'email',
            'content' => "Test Message",
            'status' => 'sent',
        ]);
    }

    public function test_notify_tool_sends_notification()
    {
        $user = User::factory()->create();
        NotificationPreference::create([
            'user_id' => $user->id,
            'channel' => 'email',
            'enabled' => true,
        ]);
        
        $agent = Agent::create(['name' => 'Notifier', 'system_prompt' => '']);
        $conversation = Conversation::create(['agent_id' => $agent->id]);
        
        // Mock the user relation on conversation (it was missing in previous tests)
        // Since schema might be missing it, we rely on NotifyTool's fallback or mock context
        // But wait, NotifyTool uses `$this->conversation?->user ?? User::first()`.
        // So User::factory()->create() is the "first" user.
        
        $tool = new NotifyTool();
        $tool->setConversation($conversation);
        
        $output = $tool->execute(['message' => 'Agent Alert', 'urgency' => 'high']);
        
        $this->assertStringContainsString('email: sent', $output);
    }
}
