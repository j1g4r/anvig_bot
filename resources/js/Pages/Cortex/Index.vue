<script setup>
import { Head } from '@inertiajs/vue3';
import Scene from '@/Components/Cortex/Scene.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    agents: {
        type: Array,
        required: true,
    },
    conversations: {
        type: Array,
        required: true,
    }
});
</script>

<template>
    <Head title="Cortex Command Center" />

    <div class="fixed inset-0 bg-black overflow-hidden">
        <!-- Overlay UI -->
        <div class="absolute top-0 left-0 w-full z-10 p-6 pointer-events-none">
            <div class="flex justify-between items-start pointer-events-auto">
                <div>
                   <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-600 tracking-tighter">
                        CORTEX
                   </h1>
                   <p class="text-blue-200 text-xs tracking-widest uppercase">Neural Command Interface</p>
                </div>
                
                <div class="flex gap-4">
                    <button class="bg-gray-900/80 backdrop-blur border border-blue-500/30 text-blue-400 px-4 py-2 rounded uppercase text-xs hover:bg-blue-900/50 transition">
                        Reset View
                    </button>
                    <a href="/dashboard" class="bg-gray-900/80 backdrop-blur border border-gray-700 text-gray-400 px-4 py-2 rounded uppercase text-xs hover:text-white transition">
                        Exit
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Overlay -->
        <div class="absolute bottom-6 left-6 z-10 pointer-events-none">
            <div class="bg-black/50 backdrop-blur p-4 rounded-lg border border-gray-800 text-xs">
                <div class="flex gap-8">
                    <div>
                        <span class="block text-gray-500 uppercase">Agents Online</span>
                        <span class="text-2xl font-mono text-blue-400">{{ agents.length }}</span>
                    </div>
                    <div>
                         <span class="block text-gray-500 uppercase">Active Links</span>
                        <span class="text-2xl font-mono text-purple-400">{{ conversations.length }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3D Scene -->
        <Scene :agents="agents" :conversations="conversations" />
    </div>
</template>
