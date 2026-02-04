<?php

namespace App\Http\Controllers;

use App\Models\Trace;
use Inertia\Inertia;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function index()
    {
        return Inertia::render('Monitoring', [
            'initialTraces' => Trace::with('agent')
                ->latest()
                ->limit(50)
                ->get()
        ]);
    }
}
