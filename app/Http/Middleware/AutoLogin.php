<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AutoLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only run if we are in Desktop Mode and not logged in
        // AND request is from localhost (Security Hardening)
        if (config('app.desktop_mode') && !Auth::check()) {
            if (in_array($request->ip(), ['127.0.0.1', '::1'])) {
                // Log in User ID 1
                Auth::loginUsingId(1);
            }
        }

        return $next($request);
    }
}
