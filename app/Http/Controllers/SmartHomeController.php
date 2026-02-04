<?php

namespace App\Http\Controllers;

use App\Models\SmartDevice;
use App\Services\SmartHomeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SmartHomeController extends Controller
{
    protected SmartHomeService $smartHomeService;

    public function __construct(SmartHomeService $smartHomeService)
    {
        $this->smartHomeService = $smartHomeService;
    }

    public function index()
    {
        $devices = SmartDevice::where('user_id', Auth::id())
            ->orderBy('room')
            ->orderBy('name')
            ->get();

        return Inertia::render('SmartHome/Index', [
            'devices' => $devices,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'room' => 'nullable|string|max:255',
            'type' => 'required|in:light,switch,thermostat,sensor,lock,cover,other',
            'protocol' => 'required|in:mqtt,http,home_assistant',
            'config' => 'required|array',
        ]);

        SmartDevice::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'room' => $request->room,
            'type' => $request->type,
            'protocol' => $request->protocol,
            'config' => $request->config,
            'state' => ['on' => false],
        ]);

        return back()->with('flash', ['message' => "Device '{$request->name}' added!"]);
    }

    public function control(Request $request, SmartDevice $device)
    {
        if ($device->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'action' => 'required|string',
            'params' => 'array',
        ]);

        $result = $this->smartHomeService->sendCommand(
            $device,
            $request->action,
            $request->params ?? []
        );

        return response()->json($result);
    }

    public function destroy(SmartDevice $device)
    {
        if ($device->user_id !== Auth::id()) {
            abort(403);
        }

        $name = $device->name;
        $device->delete();

        return back()->with('flash', ['message' => "Device '{$name}' removed."]);
    }
}
