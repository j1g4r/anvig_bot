<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Vision\VisionService;

class VisionController extends Controller
{
    public function analyse(Request $request, VisionService $vision)
    {
        $request->validate([
            'image' => 'required|image|max:10240',
            'prompt' => 'nullable|string',
            'detection_mode' => 'nullable|string:in:objects,faces,text,general'
        ]);

        $path = $request->file('image')->store('temp/frames');
        $visionPath = storage_path('app/' . $path);

        $result = $vision->analyseImage($visionPath, $request->input('prompt', 'General analysis'));

        unlink($visionPath);

        return response()->json([
            'success' => true,
            'analysis' => $result
        ]);
    }

    public function streamStart(Request $request)
    {
        $request->validate(['session_id' => 'required|string']);

        session()->put('vision_stream_active', true);
        session()->put('vision_stream_id', $request->input('session_id'));

        return response()->json([
            'stream_id' => $request->input('session_id'),
            'status' => 'active'
        ]);
    }

    public function streamStop()
    {
        session()->forget('vision_stream_active');
        session()->forget('vision_stream_id');

        return response()->json(['status' => 'stopped']);
    }
}
