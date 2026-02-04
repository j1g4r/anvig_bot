<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Jobs\ProcessAgentThought;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IncomingWebhookController extends Controller
{
    /**
     * Handle incoming webhooks to trigger agent reasoning.
     */
    public function handle(Request $request, Conversation $conversation)
    {
        // 1. Optional Token Security (Implementation could be added to Conversation metadata or a private col)
        // For now, we trust the URL ID, but in Era 3+ we'll add secret tokens.

        $payload = $request->all();
        $source = $request->header('X-Webhook-Source', 'External Service');

        Log::info("Incoming Webhook for Conversation #{$conversation->id} from {$source}");

        // 2. Inject as a SYSTEM message to trigger Jerry
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'system',
            'content' => "INCOMING WEBHOOK SIGNAL from {$source}:\n" . json_encode($payload, JSON_PRETTY_PRINT) . "\n\nPlease analyze this data and take appropriate action if necessary.",
        ]);

        // 3. Trigger the reasoning chain
        ProcessAgentThought::dispatch($conversation);

        return response()->json([
            'status' => 'success',
            'message' => 'Agent notified of incoming signal.',
        ]);
    }
}
