<?php
declare(strict_types=1);

namespace App\Http\Controllers\Vision;

use App\Http\Controllers\Controller;
use App\Models\VideoAnalysisSession;
use App\Models\VideoStreamFrame;
use App\Services\Vision\StreamManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Vision 2.0 Real-time Stream Controller
 */
class VisionController extends Controller
{
    public function __construct(
        private StreamManagerService $streamManager
    ) {}

    public function startStream(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source' => 'nullable|string|max:255',
            'fps_target' => 'nullable|integer|min:1|max:30',
        ]);

        $streamId = (string) Str::uuid();
        $source = $validated['source'] ?? 'unknown';

        $session = VideoAnalysisSession::create([
            'id' => $streamId,
            'source' => $source,
            'fps' => $validated['fps_target'] ?? 5,
            'status' => 'active',
            'started_at' => now(),
            'events_summary' => [],
        ]);

        $this->streamManager->startStream($streamId, [
            'target_fps' => $session->fps,
            'source' => $source,
        ]);

        Log::channel('vision')->info('Stream started', [
            'stream_id' => $streamId,
            'source' => $source,
        ]);

        return response()->json([
            'success' => true,
            'stream_id' => $streamId,
            'status' => 'active',
            'ingest_endpoint' => url("/api/vision/stream/ingest?stream_id={$streamId}"),
        ], 201);
    }

    public function ingestFrame(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stream_id' => 'required|uuid|exists:video_analysis_sessions,id',
            'frame_number' => 'required|integer|min:1',
            'image_data' => 'required|string',
            'motion_score' => 'nullable|numeric|min:0|max:1',
        ]);

        $streamId = $validated['stream_id'];
        $session = VideoAnalysisSession::find($streamId);

        if ($session->status !== 'active') {
            return response()->json([
                'success' => false,
                'error' => 'Stream not active',
            ], 400);
        }

        $imageData = base64_decode($validated['image_data'], true);
        if ($imageData === false) {
            return response()->json([
                'error' => 'Invalid base64',
            ], 422);
        }

        $frame = [
            'frame_number' => $validated['frame_number'],
            'image_data' => $validated['image_data'],
            'timestamp' => microtime(true),
            'motion_score' => $validated['motion_score'] ?? 0,
        ];

        $shouldSample = $this->streamManager->ingestFrame($streamId, $frame);

        VideoStreamFrame::create([
            'session_id' => $streamId,
            'frame_number' => $validated['frame_number'],
            'image_hash' => hash('xxh64', $imageData),
            'motion_score' => $frame['motion_score'],
            'sampled_for_analysis' => $shouldSample,
            'captured_at' => now(),
            'analysis_status' => $shouldSample ? 'pending' : 'skipped',
        ]);

        return response()->json([
            'success' => true,
            'frame_number' => $validated['frame_number'],
            'sampled' => $shouldSample,
        ]);
    }

    public function stopStream(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stream_id' => 'required|uuid',
        ]);

        $streamId = $validated['stream_id'];
        $session = VideoAnalysisSession::find($streamId);

        if (!$session) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $duration = $session->started_at->diffInSeconds(now());

        $session->update([
            'status' => 'completed',
            'ended_at' => now(),
            'duration_seconds' => $duration,
        ]);

        $this->streamManager->stopStream($streamId);

        return response()->json([
            'success' => true,
            'stream_id' => $streamId,
            'duration_seconds' => $duration,
            'total_frames' => VideoStreamFrame::where('session_id', $streamId)->count(),
        ]);
    }

    public function getStreamStatus(string $stream_id): JsonResponse
    {
        $session = VideoAnalysisSession::find($stream_id);
        if (!$session) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $frameCount = VideoStreamFrame::where('session_id', $stream_id)->count();
        $sampled = VideoStreamFrame::where('session_id', $stream_id)
            ->where('sampled_for_analysis', true)->count();

        return response()->json([
            'success' => true,
            'stream' => [
                'id' => $session->id,
                'source' => $session->source,
                'status' => $session->status,
                'fps_target' => $session->fps,
                'started_at' => $session->started_at?->toIso8601String(),
                'ended_at' => $session->ended_at?->toIso8601String(),
            ],
            'stats' => [
                'total_frames' => $frameCount,
                'sampled_frames' => $sampled,
            ],
        ]);
    }

    public function getSessionFrames(Request $request, string $session_id): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
        ]);

        $limit = $validated['limit'] ?? 20;
        $offset = $validated['offset'] ?? 0;

        $query = VideoStreamFrame::where('session_id', $session_id)->orderBy('frame_number');
        $total = $query->count();
        $frames = $query->skip($offset)->take($limit)->get([
            'id', 'frame_number', 'motion_score', 'sampled_for_analysis',
            'analysis_status', 'analysis_result', 'captured_at',
        ]);

        return response()->json([
            'success' => true,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total,
            ],
            'frames' => $frames,
        ]);
    }
}
