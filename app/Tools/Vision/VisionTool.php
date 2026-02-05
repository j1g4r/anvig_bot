<?php

declare(strict_types=1);

namespace App\Tools\Vision;

use App\Tools\Contracts\ToolInterface;
use App\Services\Vision\VisionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class VisionTool implements ToolInterface
{
    private VisionService $visionService;
    
    public function __construct()
    {
        $this->visionService = new VisionService();
    }

    public function getName(): string
    {
        return 'vision.analyse';
    }

    public function getDescription(): string
    {
        return 'Analyse images and video frames using AI vision models.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'image_path' => [
                    'type' => 'string',
                    'description' => 'Local file path or public URL of image'
                ],
                'prompt' => [
                    'type' => 'string',
                    'description' => 'Specific analysis prompt/question'
                ],
                'stream_mode' => [
                    'type' => 'boolean',
                    'description' => 'Enable real-time stream analysis',
                    'default' => false
                ],
                'frame_rate' => [
                    'type' => 'integer',
                    'description' => 'Frames per second for analysis',
                    'default' => 1,
                    'minimum' => 1,
                    'maximum' => 30
                ]
            ],
            'required' => ['image_path', 'prompt']
        ];
    }

    public function execute(array $params): array
    {
        $validator = Validator::make($params, [
            'image_path' => 'required|string',
            'prompt' => 'required|string',
            'stream_mode' => 'boolean',
            'frame_rate' => 'integer|between:1,30',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException(
                'Invalid parameters: ' . json_encode($validator->errors()->all())
            );
        }

        try {
            if (!empty($params['stream_mode']) && $params['stream_mode'] === true) {
                // Delegate to stream manager for real-time analysis
                $streamManager = app(\App\Services\Vision\StreamManagerService::class);
                return $streamManager->startStreamSession([
                    'input_type' => 'file',
                    'source' => $params['image_path'],
                    'extracted_text' => $params['prompt']
                ]);
            }

            // Standard image analysis
            $result = $this->visionService->analyseImage(
                $params['image_path'],
                $params['prompt']
            );

            return [
                'success' => true,
                'analysis' => $result,
                'mode' => 'static'
            ];

        } catch (\Throwable $e) {
            Log::error('VisionTool execution failed', [
                'error' => $e->getMessage(),
                'params' => $params,
                'local_time' => config('app.timezone')
            ]);

            throw $e;
        }
    }

    public function supportsStreamMode(): bool
    {
        return true;
    }
}
