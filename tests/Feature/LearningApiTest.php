<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Conversation;
use App\Models\LearningExample;
use App\Models\LearningSession;
use App\Models\Message;
use App\Models\User;
use App\Services\ContinuousLearningService;
use App\Services\InteractionCollectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningApiTest extends TestCase
{
    use RefreshDatabase;

    protected Agent $agent;
    protected Conversation $conversation;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->agent = Agent::create([
            'name' => 'Test Agent',
            'system_prompt' => 'You are a test assistant.',
        ]);
        $this->conversation = Conversation::create([
            'agent_id' => $this->agent->id,
        ]);
    }

    public function test_can_submit_feedback(): void
    {
        // Create a message first
        $message = Message::create([
            'conversation_id' => $this->conversation->id,
            'role' => 'assistant',
            'content' => 'Hello, how can I help you?',
        ]);

        // Create user message for context
        Message::create([
            'conversation_id' => $this->conversation->id,
            'role' => 'user',
            'content' => 'Hi there!',
        ]);

        $response = $this->postJson('/api/learning/feedback', [
            'message_id' => $message->id,
            'score' => 1,
            'note' => 'Great response!',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_can_list_learning_examples(): void
    {
        // Create some learning examples
        LearningExample::create([
            'agent_id' => $this->agent->id,
            'conversation_id' => $this->conversation->id,
            'user_input' => 'Test question',
            'agent_output' => 'Test answer',
            'feedback_score' => 0.8,
            'feedback_type' => 'explicit',
        ]);

        $response = $this->getJson('/api/learning/examples?agent_id=' . $this->agent->id);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonCount(1, 'data');
    }

    public function test_can_get_agent_insights(): void
    {
        // Create some learning examples
        LearningExample::create([
            'agent_id' => $this->agent->id,
            'user_input' => 'Test',
            'agent_output' => 'Response',
            'feedback_score' => 1.0,
        ]);

        $response = $this->getJson('/api/learning/insights/' . $this->agent->id);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'data' => [
                'total_examples',
                'satisfaction_rate',
                'feedback_distribution',
            ],
        ]);
    }

    public function test_can_trigger_training_session(): void
    {
        // Create enough examples for training
        for ($i = 0; $i < 5; $i++) {
            LearningExample::create([
                'agent_id' => $this->agent->id,
                'user_input' => "Question {$i}",
                'agent_output' => "Answer {$i}",
                'feedback_score' => 0.8,
            ]);
        }

        $response = $this->postJson('/api/learning/train/' . $this->agent->id);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('learning_sessions', [
            'agent_id' => $this->agent->id,
        ]);
    }

    public function test_interaction_collector_captures_interactions(): void
    {
        $collector = new InteractionCollectorService();
        
        $example = $collector->capture(
            $this->conversation,
            'What is the weather?',
            'The weather is sunny today.'
        );

        $this->assertInstanceOf(LearningExample::class, $example);
        $this->assertEquals($this->agent->id, $example->agent_id);
        $this->assertEquals('What is the weather?', $example->user_input);
        $this->assertEquals('implicit', $example->feedback_type);
    }

    public function test_continuous_learning_service_returns_insights(): void
    {
        $service = new ContinuousLearningService();
        $insights = $service->getInsights($this->agent);

        $this->assertIsArray($insights);
        $this->assertArrayHasKey('total_examples', $insights);
        $this->assertArrayHasKey('satisfaction_rate', $insights);
        $this->assertArrayHasKey('active_adaptations', $insights);
    }
}
