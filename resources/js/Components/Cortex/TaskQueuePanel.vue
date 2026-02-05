<script setup>
import { computed } from 'vue';

const props = defineProps({
    tasks: {
        type: Array,
        required: true,
        // Expected format: { id, title, agent, status, priority, progress, time }
    }
});

const priorityColor = (priority) => {
    switch(priority.toLowerCase()) {
        case 'critical': return 'text-red-500 bg-red-500/10 border-red-500/20';
        case 'high': return 'text-orange-500 bg-orange-500/10 border-orange-500/20';
        case 'medium': return 'text-yellow-500 bg-yellow-500/10 border-yellow-500/20';
        default: return 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20';
    }
};

const sortedTasks = computed(() => {
    // Sort by priority logic (Critical > High > Medium > Low)
    const pMap = { critical: 4, high: 3, medium: 2, low: 1 };
    return [...props.tasks].sort((a, b) => pMap[b.priority.toLowerCase()] - pMap[a.priority.toLowerCase()]);
});
</script>

<template>
    <div class="h-full flex flex-col bg-black/80 backdrop-blur-xl border-l border-white/10 shadow-[0_0_30px_rgba(0,0,0,0.5)] overflow-hidden">
        <!-- Header -->
        <div class="p-4 border-b border-white/10 flex justify-between items-center bg-gradient-to-r from-gray-900 to-black">
            <div>
                <h3 class="text-blue-400 font-black uppercase text-xs tracking-widest">Task Queue</h3>
                <span class="text-[0.6rem] text-gray-500 font-mono">LIVE FEED</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="relative flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                </span>
                <span class="text-xl font-mono font-bold text-white">{{ tasks.length }}</span>
            </div>
        </div>

        <!-- Filter / Tabs (Mocked) -->
        <div class="flex border-b border-white/5 text-[0.6rem] font-bold uppercase tracking-wider text-gray-500">
            <button class="flex-1 py-3 hover:bg-white/5 hover:text-white transition bg-white/5 text-blue-300 border-b-2 border-blue-500">Pending</button>
            <button class="flex-1 py-3 hover:bg-white/5 hover:text-white transition">Processing</button>
            <button class="flex-1 py-3 hover:bg-white/5 hover:text-white transition">Completed</button>
        </div>

        <!-- Task List -->
        <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-2">
            <div v-for="task in sortedTasks" :key="task.id" 
                 class="group relative bg-gray-900/50 border border-white/5 p-3 rounded hover:border-blue-500/30 hover:bg-blue-900/10 transition-all cursor-pointer">
                
                <!-- Hover Glow -->
                <div class="absolute inset-0 bg-blue-500/5 opacity-0 group-hover:opacity-100 transition-opacity rounded pointer-events-none"></div>

                <div class="flex justify-between items-start mb-2 relative z-10">
                    <span class="font-mono text-[0.6rem] text-gray-500">#{{ task.id }}</span>
                    <span class="text-[0.6rem] font-bold uppercase px-1.5 py-0.5 rounded border" :class="priorityColor(task.priority)">
                        {{ task.priority }}
                    </span>
                </div>

                <h4 class="text-xs font-bold text-gray-300 mb-1 group-hover:text-white transition-colors relative z-10">{{ task.title }}</h4>

                <div class="flex justify-between items-center text-[0.6rem] text-gray-400 mb-2 relative z-10">
                    <span class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-600"></span>
                        {{ task.agent }}
                    </span>
                    <span class="font-mono">{{ task.time }}</span>
                </div>

                <!-- Progress Bar -->
                <div class="relative w-full h-1 bg-gray-800 rounded-full overflow-hidden z-10">
                    <div class="absolute top-0 left-0 h-full bg-blue-500 transition-all duration-1000" 
                         :style="{ width: task.progress + '%' }"
                         :class="{ 'bg-emerald-500': task.progress === 100 }"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(0,0,0,0.2);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.1);
    border-radius: 2px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.2);
}
</style>
