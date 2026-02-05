<script setup>
import { Head } from '@inertiajs/vue3';
import Scene from '@/Components/Cortex/Scene.vue';
import SystemDashboard from '@/Components/Cortex/SystemDashboard.vue';
import TaskQueuePanel from '@/Components/Cortex/TaskQueuePanel.vue';
import AgentDetailPanel from '@/Components/Cortex/AgentDetailPanel.vue';
import PerformancePanel from '@/Components/Cortex/PerformancePanel.vue';
import { ref, onMounted, onBeforeUnmount, reactive, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    agents: {
        type: Array,
        required: true,
    },
    conversations: {
        type: Array,
        required: true,
    }
});

const sceneRef = ref(null);

const handleZoomIn = () => sceneRef.value?.zoomIn();
const handleZoomOut = () => sceneRef.value?.zoomOut();
const handleReset = () => sceneRef.value?.resetView();
const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
};

// Reactive state for real-time updates
const localAgents = ref([...props.agents]);
const localConversations = ref([...props.conversations]);
const commLogs = ref([]);

// Mock Stats for Phase 1
// Real Data State
const systemStats = ref({
    health: 98,
    tasksCompleted: 124,
    totalTasks: 150,
    uptime: '00:00:00',
    activeConnections: 0,
    avgResponseTime: 120,
    networkStatus: 'CONNECTING'
});

const perfHistory = ref([]);

const fetchSystemData = async () => {
    try {
        const [statusRes, statsRes, historyRes, tasksRes] = await Promise.all([
            axios.get('/api/cortex/agents/status'),
            axios.get('/api/cortex/system/stats'),
            axios.get('/api/cortex/performance/history'),
            axios.get('/api/cortex/tasks/live')
        ]);

        if (statusRes.data) localAgents.value = statusRes.data;
        if (statsRes.data) systemStats.value = statsRes.data;
        if (historyRes.data) perfHistory.value = historyRes.data;
        if (tasksRes.data) mockTasks.value = tasksRes.data;

    } catch (e) {
        console.error("Cortex Sync Error:", e);
        systemStats.value.networkStatus = 'ERROR';
    }
};

// Phase 2 State
const selectedAgent = ref(null);
const showAgentPanel = ref(false);
const showAnalytics = ref(false);

const mockTasks = ref([]);

// Selection Logic
const handleAgentSelect = (agentId) => {
    const agent = localAgents.value.find(a => a.id === agentId);
    if (agent) {
        selectedAgent.value = {
            ...agent,
            status: 'active', // Mock status for now if missing
            metrics: { cpu: 45, mem: 60 },
            tags: ['Machine Learning', 'Data Analysis', 'Python']
        };
        showAgentPanel.value = true;
    }
};

// Update Uptime
let startTime = Date.now();
const updateUptime = () => {
    const diff = Date.now() - startTime;
    const hours = Math.floor(diff / 3600000);
    const minutes = Math.floor((diff % 3600000) / 60000);
    const seconds = Math.floor((diff % 60000) / 1000);
    systemStats.uptime = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
};

const CLEANUP_INTERVAL_MS = 10000; // Check every 10s
const ACTIVITY_WINDOW_MS = 10 * 60 * 1000; // 10 minutes

const cleanupStaleLinks = () => {
    // Filter out conversations that haven't updated recently
    localConversations.value = localConversations.value.filter(c => {
        const updated = c.updated_at ? new Date(c.updated_at).getTime() : 0;
        const limit = Date.now() - ACTIVITY_WINDOW_MS;
        return updated > limit;
    });
};

let cleanupTimer;

onMounted(() => {
    console.log("Cortex: Connecting to Neural Net...");
    
    // Initial Fetch
    fetchSystemData();

    // Initial Fetch
    fetchSystemData();

    // Start Polling (Backup to Websockets)
    cleanupTimer = setInterval(() => {
        cleanupStaleLinks();
        fetchSystemData();
    }, 2000); // 2s interval

    if (window.Echo) {
        window.Echo.channel('monitoring')
            .listen('ToolExecuting', (e) => {
                console.log("⚡️ Neural Spike Detected:", e);
                
                // Communication Stream Logic
                const commTools = ['collaborate_tool', 'communication_hub', 'slack_tool', 'delegate_task', 'ask_question', 'delegate'];
                const toolName = e.tool || '';
                
                if (commTools.includes(toolName) || toolName.includes('communication') || toolName.includes('collaborate')) {
                    let message = "Activity detected...";
                    // Handle unwrapped or wrapped params
                    const params = (e.input && e.input.params) ? e.input.params : (e.input || {});
                    
                    if (params.message) message = params.message;
                    else if (params.reason) message = params.reason;
                    else if (params.question) message = params.question;
                    else if (params.instruction) message = params.instruction;
                    else if (params.text) message = params.text;
                    else if (params.command) message = `Exec: ${params.command}`;
                    
                    commLogs.value.unshift({
                        id: Date.now() + Math.random(),
                        timestamp: new Date().toLocaleTimeString(),
                        agent: `Agent ${e.agentId || '?'}`, 
                        tool: toolName,
                        message: message
                    });
                    
                    if (commLogs.value.length > 50) commLogs.value.pop();
                }

                // Update Conversations/Links
                if (e.conversationId) {
                    const existing = localConversations.value.find(c => c.id === e.conversationId);
                    const now = new Date().toISOString();
                    
                    if (!existing) {
                        localConversations.value.push({
                            id: e.conversationId,
                            agent_id: e.agentId,
                            title: `Task #${e.traceId || '?'}`,
                            participants: [], 
                            updated_at: now
                        });
                    } else {
                        existing.updated_at = now;
                    }
                }
            });
    } else {
        console.warn("Cortex: Echo not initialized. Real-time updates disabled.");
    }
});

onBeforeUnmount(() => {
    if (cleanupTimer) clearInterval(cleanupTimer);
});
</script>

<template>
    <Head title="Cortex Command Center" />

    <div class="fixed inset-0 bg-black overflow-hidden">
        <Scene 
            :agents="localAgents" 
            :conversations="localConversations"
            :systemStats="systemStats"
            :tasks="mockTasks"
            :messages="commLogs"
        />
    </div>
</template>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.5s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

/* CRT Scanlines */
.bg-scanlines {
    background: linear-gradient(
        to bottom,
        rgba(255,255,255,0),
        rgba(255,255,255,0) 50%,
        rgba(0,0,0,0.2) 50%,
        rgba(0,0,0,0.2)
    );
    background-size: 100% 4px;
}

/* Vignette */
.bg-vignette {
    background: radial-gradient(
        circle,
        rgba(0,0,0,0) 60%,
        rgba(0,0,0,0.8) 100%
    );
}
</style>
