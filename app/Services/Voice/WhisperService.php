<?php

namespace App\Services\Voice;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;
use Exception;


/**
 * Whisper Service - Speech to Text (STT)
 * 
 * Handles audio transcription using OpenAI Whisper API.
 * Leverages the openai-php/laravel package for API communication.
 * 
 * @package App\Services\Voice
 */
class WhisperService
{
    /**
     * Supported audio formats natively handled by Whisper API
     */
    const SUPPORTED_FORMATS = [
        'mp3', 'mp4', 'mpeg', 'mpga', 
        'm4a', 'wav', 'webm'
    ];

    /**
     * Maximum file size in bytes (25MB Whisper limit)
     */
    const MAX_FILE_SIZE = 25 * 1024 * 1024;

    /**
     * Transcribe audio file to text.
     *
     * @param string $filePath Path to audio file (relative to storage)
     * @param array $options Additional options (language, prompt, response_format)
     * @return array|null Result with 'text', 'full_response' or null on failure
     */
    public function transcribe(string $filePath, array $options = []): ?array
    {
        try {
            $fullPath = Storage::path($filePath);
            
            if (!file_exists($fullPath)) {
                Log::error("Whisper transcribe: File not found: {$filePath}");
                return null;
            }

            if (filesize($fullPath) > self::MAX_FILE_SIZE) {
                Log::error("Whisper transcribe: File exceeds 25MB limit: {$filePath}");
                return ['error' => 'File too large', 'status' => 'error'];
            }

            $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            if (!in_array($extension, self::SUPPORTED_FORMATS)) {
                Log::warning("Whisper transcribe: Unsupported format: {$extension}");
                return ['error' => 'Unsupported format', 'status' => 'error'];
            }

            $start = microtime(true);

            $response = OpenAI::audio()->transcribe([
                'model' => $options['model'] ?? config('services.whisper.model', 'whisper-1'),
                'file' => fopen($fullPath, 'r'),
                'language' => $options['language'] ?? null,
                'prompt' => $options['prompt'] ?? null,
                'response_format' => $options['format'] ?? 'json',
                'temperature' => $options['temperature'] ?? 0,
            ]);

            $responseTime = (microtime(true) - $start) * 1000;

            Log::info("Whisper transcription completed", [
                'file' => $filePath,
                'response_time_ms' => round($responseTime, 2),
                'text_length' => strlen($response->text ?? ''),
            ]);

            return [
                'status' => 'success',
                'text' => $response->text,
                'full_response' => $response->toArray(),
                'response_time_ms' => round($responseTime, 2),
            ];

        } catch (Exception $e) {
            Log::error("Whisper transcription failed: {$e->getMessage()}", [
                'file' => $filePath,
                'trace' => $e->getTraceAsString(),
            ]);
            return ['error' => $e->getMessage(), 'status' => 'error'];
        }
    }

    /**
     * Translate audio to English (speech translation).
     *
     * @param string $filePath Path to audio file
     * @param array $options Additional options
     * @return array|null Result or null on failure
     */
    public function translate(string $filePath, array $options = []): ?array
    {
        try {
            $fullPath = Storage::path($filePath);

            if (!file_exists($fullPath)) {
                Log::error("Whisper translate: File not found: {$filePath}");
                return null;
            }

            $start = microtime(true);

            $response = OpenAI::audio()->translate([
                'model' => $options['model'] ?? config('services.whisper.model', 'whisper-1'),
                'file' => fopen($fullPath, 'r'),
                'prompt' => $options['prompt'] ?? null,
                'response_format' => $options['format'] ?? 'json',
                'temperature' => $options['temperature'] ?? 0,
            ]);

            $responseTime = (microtime(true) - $start) * 1000;

            Log::info("Whisper translation completed", [
                'file' => $filePath,
                'response_time_ms' => round($responseTime, 2),
                'text' => $response->text,
            ]);

            return [
                'status' => 'success',
                'text' => $response->text,
                'full_response' => $response->toArray(),
                'response_time_ms' => round($responseTime, 2),
            ];

        } catch (Exception $e) {
            Log::error("Whisper translation failed: {$e->getMessage()}", [
                'file' => $filePath,
            ]);
            return ['error' => $e->getMessage(), 'status' => 'error'];
        }
    }

    /**
     * Validate audio file before processing.
     *
     * @param string $filePath Path to audio file
     * @return array Validation result with 'valid' boolean and 'message'
     */
    public function validateAudioFile(string $filePath): array
    {
        try {
            $fullPath = Storage::path($filePath);

            if (!file_exists($fullPath)) {
                return ['valid' => false, 'message' => 'File not found'];
            }

            $size = filesize($fullPath);
            if ($size > self::MAX_FILE_SIZE) {
                return ['valid' => false, 'message' => 'File exceeds 25MB limit'];
            }

            $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            if (!in_array($extension, self::SUPPORTED_FORMATS)) {
                return ['valid' => false, 'message' => "Unsupported format: {$extension}"];
            }

            return ['valid' => true, 'message' => 'File valid', 'size' => $size];

        } catch (Exception $e) {
            return ['valid' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Health check - verify OpenAI API connectivity.
     *
     * @return array Health status with available API status
     */
    public function healthCheck(): array
    {
        try {
            $start = microtime(true);
            
            // Test API with minimal models request
            $models = OpenAI::models()->list();
            
            $responseTime = (microtime(true) - $start) * 1000;
            
            $available = collect($models->data)
                ->pluck('id')
                ->contains('whisper-1');

            return [
                'status' => $available ? 'healthy' : 'degraded',
                'model_available' => $available,
                'response_time_ms' => round($responseTime, 2),
                'timestamp' => now()->toIso8601String(),
            ];

        } catch (Exception $e) {
            Log::error("Whisper health check failed: {$e->getMessage()}");
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ];
        }
    }

}
