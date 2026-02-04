<?php

namespace App\Http\Controllers;

use App\Services\GodModeService;
use Illuminate\Http\Request;

class GodModeController extends Controller
{
    public function toggle(GodModeService $service)
    {
        if ($service->isAwake()) {
            $service->sleep();
            return back()->with('message', 'System put to sleep.');
        } else {
            $service->awaken();
            return back()->with('message', 'God Mode ACTIVATED.');
        }
    }
}
