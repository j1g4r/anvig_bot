<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        // MOCK USER for No-Auth Mode
        if (!$user) {
            $user = new \App\Models\User();
            $user->id = 1;
            $user->name = 'Commander';
            $user->email = 'admin@anvig.ai';
            // Allow all nav items by default for Commander
        }

        $navVisibility = \App\Models\UserSetting::getValue($user->id, 'nav_visibility', [
            'dashboard' => true, 'agents' => true, 'memory_vault' => true, 'monitoring' => true,
            'kanban' => true, 'documents' => true, 'mfa' => true, 'galaxy' => true,
            'workflows' => true, 'smart_home' => true, 'ollama' => true,
        ]);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'god_mode' => \App\Models\SystemSetting::get('god_mode_enabled', false),
            'navVisibility' => $navVisibility,
        ];
    }
}
