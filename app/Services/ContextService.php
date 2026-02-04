<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class ContextService
{
    /**
     * Compress the conversation by summarizing old messages.
     */
    public function compress(Conversation $conversation, int $threshold = 20)
    {
        // 1. Check if we need to compress
        $messageCount = $conversation->messages()->count();
        if ($messageCount <= $threshold) {
            return false;
        }

        Log::info("Compressing Conversation #{$conversation->id} (Count: {$messageCount})");

        // 2. Identify messages to compress (e.g., the first count - 10)
        $toCompressCount = $messageCount - 10;
        $messagesToCompress = $conversation->messages()
            ->orderBy('id')
            ->limit($toCompressCount)
            ->get();

        // 3. Prepare text for AI summarization
        $historyText = "";
        foreach ($messagesToCompress as $msg) {
            $historyText .= strtoupper($msg->role) . ": " . $msg->content . "\n\n";
        }

        // 4. Request Summary from AI
        try {
            $prompt = "You are a context compression engine. Below is a conversation history.\n" .
                "Summarize this history into a single, concise narrative that preserves:\n" .
                "1. All key decisions made.\n" .
                "2. Current mission status and objectives.\n" .
                "3. Crucial facts or credentials discovered.\n" .
                "4. The current active specialist agent's progress.\n\n" .
                "Keep the summary under 500 words. Do not include tool call syntax, just pure context.\n\n" .
                "HISTORY:\n$historyText";

            $response = OpenAI::chat()->create([
                'model' => $conversation->agent->model,
                'messages' => [
                    ['role' => 'system', 'content' => $prompt]
                ],
            ]);

            $summary = $response->choices[0]->message->content;

            // 5. Update Conversation Summary
            $newSummary = $conversation->summary 
                ? "PREVIOUS SUMMARY: " . $conversation->summary . "\n\nNEW CONTEXT: " . $summary
                : $summary;

            $conversation->update(['summary' => $newSummary]);

            // 6. Hard-delete or mark compressed messages? 
            // For now, let's delete them to save DB space and keep the 'messages' relationship lean.
            // In a production app, we might 'archive' them, but here we prioritize a lean context.
            $messagesToCompress->each->delete();

            return true;

        } catch (\Exception $e) {
            Log::error("Compression Failed for Conversation #{$conversation->id}: " . $e->getMessage());
            return false;
        }
    }
}
