<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use App\Services\ToolRegistry;

class MessageController extends Controller
{
    public function store(Request $request, ToolRegistry $tools)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'stream_id' => 'nullable|string',
            'frame_data' => 'nullable|string'
        ]);

        $message = Message::create([
            'user_id' => auth()->id(),
            'content' => $validated['content']
        ]);

        if (!empty($validated['stream_id']) && !empty($validated['frame_data'])) {
            $tools->execute('vision', [
                'image_path' => $validated['frame_data'],
                'prompt' => 'Analyze this stream frame',
                'stream_mode' => true
            ]);
        }

        return response()->json(['message' => $message], 201);
    }

    public function index()
    {
        return response()->json(
            Message::with('user')->latest()->paginate(50)
        );
    }
}
