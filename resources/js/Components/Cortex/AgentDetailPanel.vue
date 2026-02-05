<script setup>
import { computed } from 'vue';

const props = defineProps({
    agent: {
        type: Object,
        required: true
    },
    isOpen: {
        type: Boolean,
        default: false
    }
});

defineEmits(['close']);

const statusColor = computed(() => {
    switch(props.agent.status) {
        case 'active': return 'text-emerald-400 border-emerald-500/50 shadow-emerald-900/50';
        case 'idle': return 'text-amber-400 border-amber-500/50 shadow-amber-900/50';
        case 'processing': return 'text-cyan-400 border-cyan-500/50 shadow-cyan-900/50';
        case 'error': return 'text-red-400 border-red-500/50 shadow-red-900/50';
        default: return 'text-gray-400 border-gray-500/50 shadow-gray-900/50';
    }
});
</script>

<template>
    <div class="fixed inset-y-0 right-0 w-[450px] bg-black/90 backdrop-blur-xl border-l border-blue-500/20 shadow-2xl z-[60] transform transition-transform duration-300 ease-out"
         :class="isOpen ? 'translate-x-0' : 'translate-x-full'">
        
        <!-- Close Button -->
        <button @click="$emit('close')" class="absolute top-4 right-4 text-gray-400 hover:text-white p-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <div v-if="agent" class="p-8 h-full overflow-y-auto custom-scrollbar">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-3 h-3 rounded-full animate-pulse bg-current" :class="statusColor.split(' ')[0]"></div>
                    <span class="text-sm font-bold uppercase tracking-widest text-gray-400">{{ agent.status || 'OFFLINE' }}</span>
                </div>
                <h2 class="text-3xl font-black text-white uppercase tracking-tighter mb-1">{{ agent.name }}</h2>
                <div class="text-blue-400 font-mono text-xs uppercase">{{ agent.model || 'GPT-4-TURBO' }}</div>
            </div>

            <!-- Tags -->
            <div class="flex flex-wrap gap-2 mb-8">
                <span v-for="tag in (agent.tags || ['General Intelligence'])" :key="tag" 
                      class="px-2 py-1 rounded bg-blue-900/30 border border-blue-500/30 text-blue-200 text-[0.6rem] font-bold uppercase tracking-wider">
                    {{ tag }}
                </span>
            </div>

            <!-- Metrics Grid -->
            <div class="grid grid-cols-2 gap-4 mb-8">
                <div class="bg-gray-900/50 p-4 rounded border border-white/5">
                    <div class="text-[0.6rem] text-gray-500 uppercase mb-1">Success Rate</div>
                    <div class="text-2xl font-mono text-emerald-400">98.2%</div>
                </div>
                <div class="bg-gray-900/50 p-4 rounded border border-white/5">
                    <div class="text-[0.6rem] text-gray-500 uppercase mb-1">Avg Response</div>
                    <div class="text-2xl font-mono text-blue-400">240ms</div>
                </div>
                <!-- Resource Usage -->
                <div class="col-span-2 bg-gray-900/50 p-4 rounded border border-white/5">
                    <div class="flex justify-between text-[0.6rem] text-gray-500 uppercase mb-2">
                        <span>CPU Usage</span>
                        <span>{{ agent.metrics?.cpu || 0 }}%</span>
                    </div>
                    <div class="w-full h-1 bg-gray-800 rounded-full overflow-hidden mb-4">
                        <div class="h-full bg-blue-500 transition-all duration-500" :style="{ width: `${agent.metrics?.cpu || 0}%` }"></div>
                    </div>

                    <div class="flex justify-between text-[0.6rem] text-gray-500 uppercase mb-2">
                        <span>Memory Usage</span>
                        <span>{{ agent.metrics?.mem || 0 }}%</span>
                    </div>
                    <div class="w-full h-1 bg-gray-800 rounded-full overflow-hidden">
                        <div class="h-full bg-purple-500 transition-all duration-500" :style="{ width: `${agent.metrics?.mem || 0}%` }"></div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div>
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 border-b border-gray-800 pb-2">Task Activity</h3>
                <div class="space-y-4">
                    <div v-for="i in 3" :key="i" class="flex gap-4">
                        <div class="text-[0.6rem] font-mono text-gray-500 pt-1">10:4{{ i }}</div>
                        <div>
                            <div class="text-xs text-blue-300 font-bold">Processed Context Chunk #{{ Math.floor(Math.random()*9000) }}</div>
                            <div class="text-[0.65rem] text-gray-500 mt-1">Token analysis completed with confidence score 0.9{{ i }}</div>
                        </div>
                    </div>
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
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.1);
}
</style>
