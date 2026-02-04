<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({
    initialTraces: Array
});

const traces = ref([...props.initialTraces]);

onMounted(() => {
    if (window.Echo) {
        window.Echo.channel('monitoring')
            .listen('ToolExecuting', (e) => {
                // Add new trace at the top
                traces.value.unshift({
                    id: e.traceId,
                    conversation_id: e.conversationId,
                    agent_id: e.agentId,
                    agent_name: e.agentName,
                    tool_name: e.toolName,
                    input: e.input,
                    status: 'executing',
                    created_at: new Date().toISOString()
                });
                
                // Keep only last 50
                if (traces.value.length > 50) traces.value.pop();
            })
            .listen('ToolExecuted', (e) => {
                const index = traces.value.findIndex(t => t.id === e.traceId);
                if (index !== -1) {
                    traces.value[index] = {
                        ...traces.value[index],
                        output: e.output,
                        duration_ms: e.durationMs,
                        status: e.status
                    };
                }
            });
    }
});

const formatTime = (dateString) => {
    return new Date(dateString).toLocaleTimeString();
};

const getStatusColor = (status) => {
    switch (status) {
        case 'executing': return 'text-amber-500 bg-amber-500/10 border-amber-500/20';
        case 'success': return 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20';
        case 'error': return 'text-rose-500 bg-rose-500/10 border-rose-500/20';
        default: return 'text-gray-500 bg-gray-500/10 border-gray-500/20';
    }
};
</script>

<template>
    <Head title="System Monitoring" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center px-4">
                <h2 class="text-xl font-black leading-tight text-gray-800 dark:text-gray-200 uppercase tracking-tighter">
                    🛰️ System Monitoring
                </h2>
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                    <span class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest">Live Tracing Enabled</span>
                </div>
            </div>
        </template>

        <div class="py-2 w-full">
            <div class="w-full px-2">
                <div class="bg-white/40 dark:bg-gray-950/40 backdrop-blur-2xl rounded-xl border border-white/20 dark:border-gray-800/50 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-white/10 dark:border-gray-800/50 bg-white/10 dark:bg-gray-900/10">
                        <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight">Recent Tool Traces</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Real-time audit of internal agent operations.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50/50 dark:bg-gray-900/50 text-[10px] uppercase tracking-widest font-black text-gray-500 dark:text-gray-400">
                                    <th class="px-4 py-2 w-24">Time</th>
                                    <th class="px-4 py-2 w-32">Agent</th>
                                    <th class="px-4 py-2 w-24">Task</th>
                                    <th class="px-4 py-2 w-32">Tool</th>
                                    <th class="px-4 py-2">Input / Output</th>
                                    <th class="px-4 py-2 w-24">Status</th>
                                    <th class="px-4 py-2 w-24 text-right">Duration</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10 dark:divide-gray-800/50">
                                <tr v-for="trace in traces" :key="trace.id" class="hover:bg-white/10 dark:hover:bg-gray-800/20 transition-colors group">
                                    <td class="px-4 py-2 align-top whitespace-nowrap">
                                        <span class="text-xs font-mono font-bold text-gray-400 dark:text-gray-500">
                                            {{ formatTime(trace.created_at) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 align-top">
                                        <span class="text-xs font-bold text-emerald-500 uppercase tracking-tight">
                                            {{ trace.agent_name || trace.agent?.name || 'Agent ' + trace.agent_id }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 align-top">
                                        <span class="text-xs font-mono font-bold text-gray-500 dark:text-gray-400">
                                            #{{ trace.conversation_id }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 align-top">
                                        <div class="flex items-center space-x-2">
                                            <div v-if="trace.tool_name === 'cognitive_process'" class="w-6 h-6 rounded bg-violet-500/10 flex items-center justify-center">
                                                <span class="text-[12px]">🧠</span>
                                            </div>
                                            <div v-else class="w-6 h-6 rounded bg-indigo-500/10 flex items-center justify-center">
                                                <span class="text-[10px] font-black text-indigo-500 uppercase">T</span>
                                            </div>
                                            <span class="text-sm font-black text-gray-900 dark:text-white tracking-tight">
                                                {{ trace.tool_name === 'cognitive_process' ? 'Thinking' : trace.tool_name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 align-top">
                                        <div class="flex flex-col space-y-2">
                                            <!-- Cognitive Process Special View -->
                                            <div v-if="trace.tool_name === 'cognitive_process'">
                                                <div v-if="trace.input.thought" class="mb-2">
                                                    <span class="text-[9px] uppercase font-bold text-violet-500 block mb-1">Thought</span>
                                                    <p class="text-xs text-gray-700 dark:text-gray-300 italic border-l-2 border-violet-500 pl-2">
                                                        "{{ trace.input.thought }}"
                                                    </p>
                                                </div>
                                                <div v-if="trace.input.plan">
                                                    <span class="text-[9px] uppercase font-bold text-blue-500 block mb-1">Plan</span>
                                                    <pre class="text-[10px] text-gray-600 dark:text-gray-400 whitespace-pre-wrap font-mono">{{ trace.input.plan }}</pre>
                                                </div>
                                            </div>

                                            <!-- Standard Tool View -->
                                            <div v-else>
                                                <!-- Input -->
                                                <div>
                                                    <span class="text-[9px] uppercase font-bold text-gray-400 block mb-1">Input</span>
                                                    <pre class="text-[11px] font-mono text-gray-600 dark:text-gray-300 bg-black/5 dark:bg-black/30 p-3 rounded-lg whitespace-pre-wrap break-all border border-gray-200 dark:border-gray-800">{{ typeof trace.input === 'string' ? trace.input : JSON.stringify(trace.input, null, 2) }}</pre>
                                                </div>
                                                <!-- Output -->
                                                <div v-if="trace.output">
                                                    <span class="text-[9px] uppercase font-bold text-emerald-500 block mb-1">Output</span>
                                                    <pre class="text-[11px] font-mono text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 p-3 rounded-lg whitespace-pre-wrap break-all border border-emerald-200 dark:border-emerald-900/50">{{ typeof trace.output === 'string' ? trace.output : JSON.stringify(trace.output, null, 2) }}</pre>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 align-top">
                                        <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-widest rounded-full border transition-all duration-300" :class="getStatusColor(trace.status)">
                                            {{ trace.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 align-top text-right">
                                        <span v-if="trace.duration_ms" class="text-xs font-mono font-bold text-indigo-500">
                                            {{ trace.duration_ms }}ms
                                        </span>
                                        <span v-else class="text-xs font-mono font-bold text-gray-400 animate-pulse">...</span>
                                    </td>
                                </tr>
                                <tr v-if="traces.length === 0">
                                    <td colspan="5" class="px-4 py-12 text-center">
                                        <p class="text-sm font-bold text-gray-400 dark:text-gray-600 uppercase tracking-widest">No traces recorded yet.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
