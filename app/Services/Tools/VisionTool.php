<?php
declare(strict_types=1);
namespace App\Services\Tools;

use App\Services\Tools\ToolInterface;
use App\Services\VisionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class VisionTool implements ToolInterface
{
    public function __construct(private VisionService $visionService) {}

    public function name(): string 
    { 
        return 'vision_tool'; 
    }

    public function description(): string 
    { 
        return 'Analyze images and video streams using AI vision models.'; 
    }

    public function parameters(): array 
    {
        return [
            'type' => 'object',
            'properties' => [
                'source' => ['type' => 'string', 'enum' => ['image_upload', 'image_url', 'video_stream', 'video_file'], 'description' => 'Type of visual source'],
                'instruction' => ['type' => 'string', 'description' => 'Analysis instruction', 'default' => 'Describe this image in detail.'],
                'image_path' => ['type' => 'string', 'description' => 'Path or URL'],
                'stream_id' => ['type' => 'string', 'description' => 'Stream session ID'],
                'frame_base64' => ['type' => 'string', 'description' => 'Base64 frame data'],
                'temporal_context' => ['type' => 'array', 'items' => ['type' => 'object']],
            ],
            'required' => ['source']
        ];
    }

    public function execute(array $input): string 
    {
        $args = array_merge($input, $input['params'] ?? []);
        $source = $args['source'] ?? '';

        if (empty($source)) {
            return json_encode(['tool' => 'vision', 'status' => 'error', 'error' => 'Source parameter required']);
        }

        $result = match ($source) {
            'image_upload', 'image_url' => $this->handleStaticImage($args),
            'video_stream' => $this->handleStreamFrame($args),
            default => ['tool' => 'vision', 'status' => 'error', 'error' => 'Unsupported source'],
        };
        
        return json_encode($result);
    }

    private function handleStaticImage(array $args): array {
        $start = microtime(true);
        $result = $this->visionService->analyzeImage($args['image_path'] ?? '', $args['instruction'] ?? 'Describe this image.');
        if (!$result) return ['tool' => 'vision', 'status' => 'error', 'error' => 'Vision analysis failed'];
        return ['tool' => 'vision', 'status' => 'success', 'duration_ms' => round((microtime(true)-$start)*1000,2), 'result' => $result];
    }
    
    private function handleStreamFrame(array $args): array {
        $start = microtime(true);
        $result = $this->visionService->analyzeFrame($args['frame_base64'] ?? '', $args['temporal_context'] ?? [], $args['metadata']['timestamp'] ?? 0.0);
        if (!$result) return ['tool' => 'vision', 'status' => 'error', 'error' => 'Stream analysis failed'];
        return ['tool' => 'vision', 'status' => 'success', 'duration_ms' => round((microtime(true)-$start)*1000,2), 'result' => array_merge($result, ['stream_id' => $args['stream_id'] ?? 'unknown'])];
    }
}
