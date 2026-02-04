<?php

namespace App\Http\Controllers;

use App\Services\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MfaController extends Controller
{
    protected TotpService $totpService;

    public function __construct(TotpService $totpService)
    {
        $this->totpService = $totpService;
    }

    /**
     * Display MFA management page.
     */
    public function index()
    {
        $userId = Auth::id();
        $services = $this->totpService->listServices($userId);

        return Inertia::render('Mfa/Index', [
            'services' => $services,
        ]);
    }

    /**
     * Register a new MFA service.
     */
    public function store(Request $request)
    {
        $request->validate([
            'service_name' => 'required|string|max:255',
            'issuer' => 'nullable|string|max:255',
        ]);

        $userId = Auth::id();
        $result = $this->totpService->register(
            $userId,
            $request->service_name,
            $request->issuer ?? 'Anvig'
        );

        return back()->with('flash', [
            'message' => "MFA registered for {$request->service_name}",
            'secret' => $result['secret'],
            'provisioning_uri' => $result['provisioning_uri'],
        ]);
    }

    /**
     * Generate a TOTP code.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'service_name' => 'required|string',
        ]);

        $userId = Auth::id();
        $code = $this->totpService->generateCode($userId, $request->service_name);

        if ($code === null) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        return response()->json(['code' => $code, 'expires_in' => 30]);
    }

    /**
     * Remove a service.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'service_name' => 'required|string',
        ]);

        $userId = Auth::id();
        $this->totpService->removeService($userId, $request->service_name);

        return back()->with('flash', [
            'message' => "Removed MFA for {$request->service_name}",
        ]);
    }
}
