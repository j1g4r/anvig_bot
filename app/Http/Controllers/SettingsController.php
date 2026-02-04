<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SettingsController extends Controller
{
    // Default navigation items - easy to extend
    protected array $navItems = [
        'dashboard' => ['label' => 'Dashboard', 'icon' => '🏠', 'default' => true, 'locked' => true],
        'agents' => ['label' => 'Agents', 'icon' => '🤖', 'default' => true, 'locked' => true],
        'memory_vault' => ['label' => 'Memory Vault', 'icon' => '🧠', 'default' => true],
        'monitoring' => ['label' => 'Monitoring', 'icon' => '📊', 'default' => true],
        'kanban' => ['label' => 'Kanban', 'icon' => '📋', 'default' => true],
        'documents' => ['label' => 'Documents', 'icon' => '📄', 'default' => true],
        'mfa' => ['label' => 'MFA', 'icon' => '🔐', 'default' => true],
        'galaxy' => ['label' => 'Memory Galaxy', 'icon' => '🌌', 'default' => true],
        'workflows' => ['label' => 'Workflows', 'icon' => '⚙️', 'default' => true],
        'smart_home' => ['label' => 'Smart Home', 'icon' => '🏠', 'default' => true],
    ];

    public function index()
    {
        $visibility = UserSetting::getValue(Auth::id(), 'nav_visibility', $this->getDefaults());

        return Inertia::render('Settings/Index', [
            'navItems' => $this->navItems,
            'navVisibility' => $visibility,
        ]);
    }

    public function updateNavVisibility(Request $request)
    {
        $request->validate([
            'visibility' => 'required|array',
        ]);

        // Ensure locked items stay visible
        $visibility = $request->visibility;
        foreach ($this->navItems as $key => $item) {
            if ($item['locked'] ?? false) {
                $visibility[$key] = true;
            }
        }

        UserSetting::setValue(Auth::id(), 'nav_visibility', $visibility);

        return back()->with('flash', ['message' => 'Navigation settings saved!']);
    }

    protected function getDefaults(): array
    {
        $defaults = [];
        foreach ($this->navItems as $key => $item) {
            $defaults[$key] = $item['default'] ?? true;
        }
        return $defaults;
    }
}
