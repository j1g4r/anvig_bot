<?php

namespace App\Services\Voice;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class ElevenLabsService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = config("services.elevenlabs.api_key", "");
        $this->baseUrl = config("services.elevenlabs.base_url", "https://api.elevenlabs.io/v1");
        $this->timeout = config("services.elevenlabs.timeout", 60);
    }

    public function textToSpeech(string $text, string $voiceId = "21m00Tcm4TlvDq8ikWAM", array $options = []): array
    {
        $startTime = microtime(true);
        try {
            if (empty($this->apiKey)) {
                Log::error("ElevenLabs API key not configured");
                return ["success" => false, "error" => "API key not configured", "created_at" => now()->toIso8601String()];
            }
            $response = Http::withHeaders(["xi-api-key" => $this->apiKey, "Content-Type" => "application/json"])
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/text-to-speech/{$voiceId}", [
                    "text" => $text,
                    "model_id" => $options["model"] ?? "eleven_multilingual_v2",
                    "voice_settings" => ["stability" => $options["stability"] ?? 0.5, "similarity_boost" => $options["similarity_boost"] ?? 0.5]
                ]);
            if (!$response->successful()) {
                return ["success" => false, "error" => "TTS failed: " . $response->status(), "created_at" => now()->toIso8601String()];
            }
            $audioContent = $response->body();
            if (isset($options["output_path"])) {
                Storage::disk("local")->put($options["output_path"], $audioContent);
            }
            return [
                "success" => true,
                "audio_content" => base64_encode($audioContent),
                "audio_bytes" => strlen($audioContent),
                "format" => "mp3",
                "response_time_ms" => round((microtime(true) - $startTime) * 1000, 2),
                "created_at" => now()->toIso8601String()
            ];
        } catch (Exception $e) {
            return ["success" => false, "error" => $e->getMessage(), "created_at" => now()->toIso8601String()];
        }
    }

    public function streamTextToSpeech(string $text, string $voiceId = "21m00Tcm4TlvDq8ikWAM", array $options = []): array
    {
        try {
            if (empty($this->apiKey)) {
                return ["success" => false, "error" => "API key not configured"];
            }
            $response = Http::withHeaders(["xi-api-key" => $this->apiKey])
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/text-to-speech/{$voiceId}/stream", [
                    "text" => $text,
                    "model_id" => $options["model"] ?? "eleven_multilingual_v2",
                    "optimize_streaming_latency" => $options["latency"] ?? 3
                ]);
            return ["success" => $response->successful(), "audio_content" => base64_encode($response->body())];
        } catch (Exception $e) {
            return ["success" => false, "error" => $e->getMessage()];
        }
    }

    public function getVoices(): array
    {
        try {
            if (empty($this->apiKey)) {
                return ["success" => false, "error" => "API key not configured"];
            }
            $response = Http::withHeaders(["xi-api-key" => $this->apiKey])
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/voices");
            $voices = $response->json()["voices"] ?? [];
            return ["success" => true, "voices" => $voices, "count" => count($voices)];
        } catch (Exception $e) {
            return ["success" => false, "error" => $e->getMessage()];
        }
    }

    public function healthCheck(): array
    {
        $startTime = microtime(true);
        try {
            if (empty($this->apiKey)) {
                return ["healthy" => false, "error" => "API key not configured"];
            }
            $response = Http::withHeaders(["xi-api-key" => $this->apiKey, "Content-Type" => "application/json"])
                ->timeout(10)
                ->get("{$this->baseUrl}/voices");
            return [
                "healthy" => $response->successful(),
                "service" => "elevenlabs",
                "response_time_ms" => round((microtime(true) - $startTime) * 1000, 2),
                "checked_at" => now()->toIso8601String()
            ];
        } catch (Exception $e) {
            return ["healthy" => false, "error" => $e->getMessage()];
        }
    }
}