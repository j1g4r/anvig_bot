<?php

namespace App\Plugins;

use App\Services\Tools\ToolInterface;

/**
 * Base class for all Anvig Plugins.
 * Developers should extend this class to register tools and agents.
 */
abstract class PluginBase
{
    /**
     * Get the display name of the plugin.
     */
    abstract public function name(): string;

    /**
     * Get the description of the plugin.
     */
    abstract public function description(): string;

    /**
     * Return an array of ToolInterface instances to register.
     * @return ToolInterface[]
     */
    public function tools(): array
    {
        return [];
    }

    /**
     * Return an array of agent configurations (optional).
     */
    public function agents(): array
    {
        return [];
    }

    /**
     * Boot method called when the plugin is loaded.
     * Use this to register routes, listeners, or other services.
     */
    public function boot(): void
    {
        // Optional boot logic
    }
}
