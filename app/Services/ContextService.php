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

        $existingSummary = $conversation->summary ? "EXISTING SUMMARY:\n" . $conversation->summary . "\n\n" : "";

        // 4. Request Summary from AI
        try {
            $prompt = "You are a context compression engine. " .
                "Your goal is to MERGE the 'Existing Summary' (if any) with the 'Recent History' into a SINGLE, updated narrative.\n\n" .
                "RULES:\n" .
                "1. Do NOT simply append the new events. Rewrite the narrative to incorporate them.\n" .
                "2. Drop minor details or resolved sub-tasks.\n" .
                "3. Preserve critical decisions, active objectives, and permanent facts.\n" .
                "4. Keep the total length under 600 words.\n\n" .
                $existingSummary .
                "RECENT HISTORY TO MERGE:\n$historyText";

            $ai = new \App\Services\AI\AIService();
            $response = $ai->chat([
                'model' => $conversation->agent->model,
                'messages' => [
                    ['role' => 'system', 'content' => $prompt]
                ],
            ], ['context' => 'compression', 'conversation_id' => $conversation->id]);

            $summary = $response->choices[0]->message->content;

            // 5. Update Conversation Summary (Overwrite with new merged version)
            $conversation->update(['summary' => $summary]);

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
