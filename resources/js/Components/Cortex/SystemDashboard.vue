<script setup>
import { computed } from 'vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            health: 98,
            tasksCompleted: 847,
            totalTasks: 1000,
            uptime: '04:22:15',
            activeConnections: 12,
            avgResponseTime: 45, // ms
            networkStatus: 'HEALTHY'
        })
    }
});

const healthColor = computed(() => {
    if (props.stats.health > 80) return 'text-emerald-400';
    if (props.stats.health > 50) return 'text-amber-400';
    return 'text-rose-500';
});

const networkColor = computed(() => {
    switch (props.stats.networkStatus) {
        case 'HEALTHY': return 'text-emerald-400';
        case 'WARNING': return 'text-amber-400';
        case 'CRITICAL': return 'text-rose-500';
        default: return 'text-gray-400';
    }
});

const taskPercentage = computed(() => {
    if (props.stats.totalTasks === 0) return 0;
    return Math.round((props.stats.tasksCompleted / props.stats.totalTasks) * 100);
});
</script>

<template>
    <div class="pointer-events-auto w-[800px] h-[100px] bg-black/80 backdrop-blur-xl border border-blue-500/30 rounded-full shadow-[0_0_30px_rgba(59,130,246,0.15)] flex items-center justify-between px-8 relative overflow-hidden group">
        <!-- Shine Effect -->
        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000 ease-in-out pointer-events-none"></div>

        <!-- Health Gauge -->
        <div class="flex items-center gap-4 border-r border-white/10 pr-6">
            <div class="relative w-16 h-16 flex items-center justify-center">
                <!-- SVG Circle Gauge -->
                <svg class="w-full h-full transform -rotate-90">
                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="transparent" class="text-gray-800" />
                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="transparent" 
                        :class="healthColor" 
                        :stroke-dasharray="2 * Math.PI * 28" 
                        :stroke-dashoffset="2 * Math.PI * 28 * (1 - stats.health / 100)"
                        class="transition-all duration-1000 ease-out" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                    <span class="text-lg font-black leading-none text-white">{{ stats.health }}%</span>
                    <span class="text-[0.5rem] uppercase text-gray-400 tracking-wider">Health</span>
                </div>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="flex-1 grid grid-cols-4 gap-6 px-6">
            <!-- Global Tasks -->
            <div class="flex flex-col">
                <span class="text-[0.6rem] uppercase tracking-widest text-blue-300/70 mb-1">Global Tasks</span>
                <div class="flex items-end gap-2">
                    <span class="text-xl font-mono font-bold text-white leading-none">{{ stats.tasksCompleted }}</span>
                    <span class="text-xs text-gray-500 mb-0.5">/ {{ stats.totalTasks }}</span>
                </div>
                <div class="w-full h-1 bg-gray-800 rounded-full mt-2 overflow-hidden">
                    <div class="h-full bg-blue-500 transition-all duration-500" :style="{ width: `${taskPercentage}%` }"></div>
                </div>
            </div>

            <!-- Uptime -->
            <div class="flex flex-col">
                <span class="text-[0.6rem] uppercase tracking-widest text-blue-300/70 mb-1">System Uptime</span>
                <span class="text-xl font-mono font-bold text-white leading-none tracking-tight">{{ stats.uptime }}</span>
                <span class="text-[0.6rem] text-emerald-400 mt-1">● Online</span>
            </div>

            <!-- Response Time -->
            <div class="flex flex-col">
                <span class="text-[0.6rem] uppercase tracking-widest text-blue-300/70 mb-1">Avg Response</span>
                <div class="flex items-end gap-2">
                    <span class="text-xl font-mono font-bold text-white leading-none">{{ stats.avgResponseTime }}<span class="text-sm text-gray-500 font-normal">ms</span></span>
                    <span class="text-xs text-emerald-400 mb-0.5">↓ 12%</span>
                </div>
            </div>
             <!-- Network -->
            <div class="flex flex-col">
                <span class="text-[0.6rem] uppercase tracking-widest text-blue-300/70 mb-1">Network</span>
                <span class="text-lg font-bold leading-none tracking-wider" :class="networkColor">{{ stats.networkStatus }}</span>
                <span class="text-[0.6rem] text-gray-400 mt-1">{{ stats.activeConnections }} Active Links</span>
            </div>
        </div>
    </div>
</template>
