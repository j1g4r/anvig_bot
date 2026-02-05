<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Voice\WhisperService;
use App\Services\Voice\ElevenLabsService;
use Illuminate\Support\Facades\Log;

class VoiceController extends Controller
{
    public function __construct(
        protected WhisperService $whisper,
        protected ElevenLabsService $elevenLabs
    ) {}

    /**
     * Transcribe audio file to text (STT)
     */
    public function transcribe(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|mimes:webm,mp3,wav,m4a|max:25600', // 25MB max
        ]);

        try {
            $file = $request->file('audio');
            
            // Store temporarily
            $path = $file->storeAs('temp-audio', 'upload_' . time() . '.' . $file->getClientOriginalExtension());

            $result = $this->whisper->transcribe($path);

            // Cleanup
            @unlink(storage_path('app/' . $path));

            if (!$result || ($result['status'] ?? '') === 'error') {
                return response()->json(['error' => $result['error'] ?? 'Transcription failed'], 500);
            }

            return response()->json([
                'text' => $result['text'],
                'confidence' => 0.99 // Whisper doesn't always return confidence, assuming high for now
            ]);

        } catch (\Exception $e) {
            Log::error("VoiceController Transcribe Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Synthesize text to speech (TTS)
     */
    public function speak(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:5000',
            'voice_id' => 'nullable|string',
        ]);

        try {
            $text = $request->input('text');
            $voiceId = $request->input('voice_id', '21m00Tcm4TlvDq8ikWAM'); // Default Rachel

            // Use Streaming for lower latency perception
            $result = $this->elevenLabs->textToSpeech($text, $voiceId);

            if (!$result['success']) {
                return response()->json(['error' => $result['error']], 500);
            }

            return response()->json([
                'audio' => $result['audio_content'], // Base64 encoded MP3
                'format' => 'mp3'
            ]);

        } catch (\Exception $e) {
            Log::error("VoiceController Speak Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
