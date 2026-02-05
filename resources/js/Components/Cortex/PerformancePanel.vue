<script setup>
import { computed } from 'vue';

const props = defineProps({
    isOpen: Boolean,
    agents: Array
});

defineEmits(['toggle']);

// Mock Data Generation for History
const historyPoints = Array.from({ length: 24 }, (_, i) => ({
    hour: i,
    efficiency: 60 + Math.random() * 35
}));

const getSparklinePoints = () => {
    return Array.from({ length: 20 }, (_, i) => 
        `${(i/20)*100},${100 - (Math.random() * 100)}`
    ).join(' ');
};

const sortedAgents = computed(() => {
    // Sort by efficiency (mock calculation)
    return [...props.agents].map(a => ({
        ...a,
        efficiency: Math.floor(Math.random() * 30) + 70, // 70-100
        cpuTrend: getSparklinePoints(),
        memTrend: getSparklinePoints()
    })).sort((a, b) => b.efficiency - a.efficiency);
});
</script>

<template>
    <div class="fixed bottom-0 left-0 right-0 bg-black/95 backdrop-blur-2xl border-t border-blue-500/30 shadow-[0_-10px_40px_rgba(0,0,0,0.7)] transition-transform duration-500 ease-in-out z-50 flex flex-col"
         :class="isOpen ? 'translate-y-0 h-[350px]' : 'translate-y-full h-0 overflow-hidden'">
        
        <!-- Toggle Handle (Always Visible) -->
        <div class="absolute -top-6 left-1/2 -translate-x-1/2 bg-black/80 border-t border-l border-r border-blue-500/30 rounded-t-xl px-6 py-1 cursor-pointer hover:bg-blue-900/40 transition"
             @click="$emit('toggle')">
             <div class="w-12 h-1 bg-gray-500 rounded-full mx-auto mb-1"></div>
             <span class="text-[0.6rem] font-bold uppercase tracking-widest text-blue-400">System Analytics</span>
        </div>

        <div class="flex-1 p-6 grid grid-cols-4 gap-8 min-h-0">
            <!-- 1. System Efficiency History (Line Chart) -->
             <div class="col-span-1 bg-gray-900/50 rounded-xl border border-white/5 p-4 flex flex-col relative overflow-hidden">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">24h System Efficiency</h3>
                
                <div class="flex-1 relative border-l border-b border-white/10">
                    <!-- Simple SVG Line Chart -->
                    <svg class="absolute inset-0 w-full h-full p-2" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <!-- Grid Lines -->
                        <line x1="0" y1="25" x2="100" y2="25" stroke="rgba(255,255,255,0.05)" stroke-width="0.5" />
                        <line x1="0" y1="50" x2="100" y2="50" stroke="rgba(255,255,255,0.05)" stroke-width="0.5" />
                        <line x1="0" y1="75" x2="100" y2="75" stroke="rgba(255,255,255,0.05)" stroke-width="0.5" />

                        <!-- Data Line -->
                        <polyline
                            :points="historyPoints.map((p, i) => `${(i/23)*100},${100-p.efficiency}`).join(' ')"
                            fill="none"
                            stroke="#3b82f6"
                            stroke-width="2"
                            vector-effect="non-scaling-stroke"
                        />
                        <!-- Fill gradient area logic omitted for brevity in SVG, using simpler stroke -->
                    </svg>
                </div>
                <div class="flex justify-between text-[0.6rem] text-gray-500 mt-2 font-mono">
                    <span>-24h</span>
                    <span>-12h</span>
                    <span>Now</span>
                </div>
            </div>

            <!-- 2. Agent Rankings Table -->
            <div class="col-span-2 bg-gray-900/50 rounded-xl border border-white/5 p-4 flex flex-col overflow-hidden">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 flex justify-between">
                    <span>Agent Efficiency Ranking</span>
                    <span class="text-blue-500">Live Updating</span>
                </h3>

                <div class="overflow-y-auto custom-scrollbar pr-2">
                    <table class="w-full text-left border-collapse">
                        <thead class="text-[0.6rem] text-gray-500 uppercase border-b border-white/10">
                            <tr>
                                <th class="pb-2 font-medium">Rank</th>
                                <th class="pb-2 font-medium">Agent</th>
                                <th class="pb-2 font-medium">Efficiency Score</th>
                                <th class="pb-2 font-medium">CPU Trend</th>
                                <th class="pb-2 font-medium">Mem Trend</th>
                            </tr>
                        </thead>
                        <tbody class="text-xs">
                            <tr v-for="(agent, idx) in sortedAgents" :key="agent.id" class="group hover:bg-white/5 transition">
                                <td class="py-3 font-mono text-gray-500">
                                    <span v-if="idx===0" class="text-yellow-400">🏆</span>
                                    <span v-else>#{{ idx + 1 }}</span>
                                </td>
                                <td class="py-3 font-bold text-blue-100 group-hover:text-blue-400 transition">{{ agent.name }}</td>
                                <td class="py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-24 h-1.5 bg-gray-700 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-1000" 
                                                 :class="agent.efficiency > 90 ? 'bg-emerald-500' : (agent.efficiency > 75 ? 'bg-blue-500' : 'bg-amber-500')"
                                                 :style="{ width: `${agent.efficiency}%` }"></div>
                                        </div>
                                        <span class="font-mono">{{ agent.efficiency }}%</span>
                                    </div>
                                </td>
                                <td class="py-3 w-24">
                                     <svg class="w-full h-8" viewBox="0 0 100 100" preserveAspectRatio="none">
                                        <polyline :points="agent.cpuTrend" fill="none" stroke="#60a5fa" stroke-width="2" vector-effect="non-scaling-stroke" />
                                     </svg>
                                </td>
                                <td class="py-3 w-24">
                                     <svg class="w-full h-8" viewBox="0 0 100 100" preserveAspectRatio="none">
                                        <polyline :points="agent.memTrend" fill="none" stroke="#a78bfa" stroke-width="2" vector-effect="non-scaling-stroke" />
                                     </svg>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. Resource Summary -->
            <div class="col-span-1 bg-gray-900/50 rounded-xl border border-white/5 p-4 flex flex-col gap-4">
                 <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Cluster Resources</h3>
                 
                 <div class="flex-1 flex items-center justify-center relative">
                     <!-- Circular Pie Mock -->
                     <div class="w-32 h-32 rounded-full border-8 border-gray-800 relative flex items-center justify-center">
                         <div class="absolute inset-0 rounded-full border-8 border-blue-500 border-t-transparent animate-spin-slow opacity-50"></div>
                         <div class="text-center">
                             <div class="text-2xl font-black text-white">42%</div>
                             <div class="text-[0.6rem] uppercase text-gray-500">Load Avg</div>
                         </div>
                     </div>
                 </div>

                 <div class="grid grid-cols-2 gap-2 text-center text-xs">
                     <div class="bg-black/40 p-2 rounded border border-white/5">
                         <div class="text-gray-500 mb-1">Total Cores</div>
                         <div class="font-mono text-white">32</div>
                     </div>
                     <div class="bg-black/40 p-2 rounded border border-white/5">
                         <div class="text-gray-500 mb-1">Total RAM</div>
                         <div class="font-mono text-white">128GB</div>
                     </div>
                 </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(0,0,0,0.2);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.1);
    border-radius: 3px;
}
.animate-spin-slow {
    animation: spin 8s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
