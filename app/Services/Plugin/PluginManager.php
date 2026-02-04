<?php

namespace App\Services\Plugin;

use App\Plugins\PluginBase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PluginManager
{
    protected array $loadedPlugins = [];

    public function __construct()
    {
        $this->discover();
    }

    /**
     * Discover and load all plugins in app/Plugins.
     */
    public function discover(): void
    {
        $pluginDir = app_path('Plugins');

        if (!File::exists($pluginDir)) {
            return;
        }

        $directories = File::directories($pluginDir);

        foreach ($directories as $dir) {
            $pluginName = basename($dir);
            $className = "App\\Plugins\\{$pluginName}\\{$pluginName}Plugin";

            if (class_exists($className)) {
                $plugin = new $className();
                if ($plugin instanceof PluginBase) {
                    $this->loadedPlugins[$pluginName] = $plugin;
                    $plugin->boot();
                }
            }
        }
    }

    /**
     * Get all registered tools from all loaded plugins.
     */
    public function getAllTools(): array
    {
        $tools = [];
        foreach ($this->loadedPlugins as $plugin) {
            foreach ($plugin->tools() as $tool) {
                $tools[$tool->name()] = $tool;
            }
        }
        return $tools;
    }

    /**
     * Get a list of loaded plugins.
     */
    public function getPlugins(): array
    {
        return $this->loadedPlugins;
    }
}
