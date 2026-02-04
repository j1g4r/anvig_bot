<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    workflow: Object,
    nodes: Array,
    edges: Array,
});

const workflowName = ref(props.workflow?.name || 'New Workflow');
const workflowDescription = ref(props.workflow?.description || '');
const workflowNodes = ref(props.nodes?.map(n => ({
    id: n.node_id,
    type: n.type,
    action_type: n.action_type,
    config: n.config || {},
    position: n.position || { x: 100, y: 100 },
})) || []);
const workflowEdges = ref(props.edges?.map(e => ({
    source: e.source_node_id,
    target: e.target_node_id,
    sourceHandle: e.source_handle,
})) || []);

const selectedNode = ref(null);
const connectingFrom = ref(null); // For click-to-connect
const nodeCounter = ref(1);

// Dragging state
const isDragging = ref(false);
const dragNode = ref(null);
const dragOffset = ref({ x: 0, y: 0 });

const nodeTypes = [
    { type: 'trigger', action_type: 'schedule', label: '⏰ Schedule', color: 'from-purple-600 to-purple-800', icon: '⏰' },
    { type: 'trigger', action_type: 'webhook', label: '🌐 Webhook', color: 'from-purple-500 to-purple-700', icon: '🌐' },
    { type: 'action', action_type: 'jerry', label: '🤖 Ask Jerry', color: 'from-blue-500 to-blue-700', icon: '🤖' },
    { type: 'action', action_type: 'shell', label: '💻 Shell', color: 'from-gray-500 to-gray-700', icon: '💻' },
    { type: 'action', action_type: 'http', label: '📡 HTTP', color: 'from-green-500 to-green-700', icon: '📡' },
    { type: 'action', action_type: 'email', label: '✉️ Email', color: 'from-yellow-500 to-yellow-700', icon: '✉️' },
    { type: 'condition', action_type: 'if_else', label: '🔀 If/Else', color: 'from-orange-500 to-orange-700', icon: '🔀' },
];

const addNode = (nodeType) => {
    const newNode = {
        id: `node_${Date.now()}_${nodeCounter.value++}`,
        type: nodeType.type,
        action_type: nodeType.action_type,
        config: getDefaultConfig(nodeType.action_type),
        position: { x: 250 + Math.random() * 100, y: 80 + workflowNodes.value.length * 100 },
    };
    workflowNodes.value.push(newNode);
    selectedNode.value = newNode;
};

const getDefaultConfig = (actionType) => {
    switch (actionType) {
        case 'schedule': return { cron: '0 9 * * *' };
        case 'webhook': return { path: '/webhook/my-hook' };
        case 'jerry': return { prompt: 'Hello Jerry!' };
        case 'shell': return { command: 'echo "Hello"' };
        case 'http': return { url: 'https://api.example.com', method: 'GET' };
        case 'email': return { to: '', subject: '', body: '' };
        case 'if_else': return { field: '', operator: 'equals', value: '' };
        default: return {};
    }
};

const removeNode = (nodeId) => {
    workflowNodes.value = workflowNodes.value.filter(n => n.id !== nodeId);
    workflowEdges.value = workflowEdges.value.filter(e => e.source !== nodeId && e.target !== nodeId);
    if (selectedNode.value?.id === nodeId) selectedNode.value = null;
};

// Click-to-connect: click source node output, then click target node input
const startConnect = (nodeId, event) => {
    event.stopPropagation();
    connectingFrom.value = nodeId;
};

const finishConnect = (nodeId, event) => {
    event.stopPropagation();
    if (connectingFrom.value && connectingFrom.value !== nodeId) {
        const exists = workflowEdges.value.some(
            e => e.source === connectingFrom.value && e.target === nodeId
        );
        if (!exists) {
            workflowEdges.value.push({ source: connectingFrom.value, target: nodeId, sourceHandle: null });
        }
    }
    connectingFrom.value = null;
};

const cancelConnect = () => {
    connectingFrom.value = null;
};

const removeEdge = (index) => {
    workflowEdges.value.splice(index, 1);
};

// Drag handlers for moving nodes
const startDrag = (node, event) => {
    isDragging.value = true;
    dragNode.value = node;
    const rect = event.target.closest('.workflow-node').getBoundingClientRect();
    dragOffset.value = {
        x: event.clientX - rect.left,
        y: event.clientY - rect.top,
    };
    selectedNode.value = node;
};

const onDrag = (event) => {
    if (!isDragging.value || !dragNode.value) return;
    const canvas = document.getElementById('workflow-canvas');
    const canvasRect = canvas.getBoundingClientRect();
    dragNode.value.position = {
        x: Math.max(0, event.clientX - canvasRect.left - dragOffset.value.x),
        y: Math.max(0, event.clientY - canvasRect.top - dragOffset.value.y),
    };
};

const stopDrag = () => {
    isDragging.value = false;
    dragNode.value = null;
};

const getNodeLabel = (node) => nodeTypes.find(t => t.action_type === node.action_type)?.label || node.action_type;
const getNodeColor = (node) => nodeTypes.find(t => t.action_type === node.action_type)?.color || 'from-gray-500 to-gray-700';
const getNodeIcon = (node) => nodeTypes.find(t => t.action_type === node.action_type)?.icon || '📦';

// Compute edge paths for SVG arrows
const edgePaths = computed(() => {
    return workflowEdges.value.map(edge => {
        const sourceNode = workflowNodes.value.find(n => n.id === edge.source);
        const targetNode = workflowNodes.value.find(n => n.id === edge.target);
        if (!sourceNode || !targetNode) return null;

        const sx = sourceNode.position.x + 90; // center-right of node
        const sy = sourceNode.position.y + 35;
        const tx = targetNode.position.x; // left of target
        const ty = targetNode.position.y + 35;

        // Bezier curve
        const midX = (sx + tx) / 2;
        const path = `M ${sx} ${sy} C ${midX} ${sy}, ${midX} ${ty}, ${tx} ${ty}`;
        return { path, edge };
    }).filter(Boolean);
});

const saveWorkflow = () => {
    const data = {
        name: workflowName.value,
        description: workflowDescription.value,
        nodes: workflowNodes.value,
        edges: workflowEdges.value,
    };
    if (props.workflow) {
        router.put(route('workflows.update', props.workflow.id), data);
    } else {
        router.post(route('workflows.store'), data);
    }
};
</script>

<template>
    <Head title="Workflow Editor" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <input
                    v-model="workflowName"
                    class="bg-transparent text-xl font-semibold text-white border-b border-transparent hover:border-gray-600 focus:border-indigo-500 focus:outline-none px-1"
                    placeholder="Workflow Name"
                />
                <div class="flex gap-2">
                    <span v-if="connectingFrom" class="text-yellow-400 text-sm animate-pulse">
                        🔗 Click target node to connect...
                    </span>
                    <button
                        @click="saveWorkflow"
                        class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-700 hover:from-green-600 hover:to-green-800 text-white rounded-lg shadow-lg transition-all duration-200"
                    >
                        💾 Save
                    </button>
                </div>
            </div>
        </template>

        <div class="flex h-[calc(100vh-180px)]" @click="cancelConnect">
            <!-- Node Palette -->
            <div class="w-56 bg-gray-800/80 backdrop-blur p-4 overflow-y-auto border-r border-gray-700">
                <h3 class="text-white font-medium mb-3 text-sm uppercase tracking-wide">Nodes</h3>
                <div class="space-y-2">
                    <button
                        v-for="nodeType in nodeTypes"
                        :key="nodeType.action_type"
                        @click="addNode(nodeType)"
                        :class="['w-full px-3 py-2 text-white rounded-lg text-left bg-gradient-to-r shadow-md hover:scale-105 transition-transform duration-150', nodeType.color]"
                    >
                        <span class="text-lg mr-2">{{ nodeType.icon }}</span>
                        {{ nodeType.label }}
                    </button>
                </div>

                <h3 class="text-white font-medium mt-6 mb-2 text-sm uppercase tracking-wide">Edges</h3>
                <div class="space-y-1 text-xs max-h-40 overflow-y-auto">
                    <div
                        v-for="(edge, i) in workflowEdges"
                        :key="i"
                        class="flex justify-between items-center text-gray-300 bg-gray-700/50 px-2 py-1 rounded"
                    >
                        <span class="truncate">{{ edge.source.slice(-8) }} → {{ edge.target.slice(-8) }}</span>
                        <button @click="removeEdge(i)" class="text-red-400 hover:text-red-300 ml-1">✕</button>
                    </div>
                    <div v-if="workflowEdges.length === 0" class="text-gray-500">No connections</div>
                </div>
            </div>

            <!-- Canvas -->
            <div
                id="workflow-canvas"
                class="flex-1 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 overflow-auto relative"
                style="background-image: radial-gradient(circle, rgba(255,255,255,0.03) 1px, transparent 1px); background-size: 20px 20px;"
                @mousemove="onDrag"
                @mouseup="stopDrag"
                @mouseleave="stopDrag"
            >
                <!-- SVG Arrows -->
                <svg class="absolute inset-0 w-full h-full pointer-events-none" style="min-height: 800px; min-width: 1200px;">
                    <defs>
                        <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                            <polygon points="0 0, 10 3.5, 0 7" fill="#6366f1" />
                        </marker>
                    </defs>
                    <path
                        v-for="(ep, i) in edgePaths"
                        :key="i"
                        :d="ep.path"
                        fill="none"
                        stroke="url(#edgeGradient)"
                        stroke-width="2"
                        marker-end="url(#arrowhead)"
                        class="transition-all duration-200"
                    />
                    <linearGradient id="edgeGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#8b5cf6" />
                        <stop offset="100%" stop-color="#6366f1" />
                    </linearGradient>
                </svg>

                <!-- Nodes -->
                <div
                    v-for="node in workflowNodes"
                    :key="node.id"
                    :style="{ left: node.position.x + 'px', top: node.position.y + 'px' }"
                    :class="[
                        'workflow-node absolute p-3 rounded-xl cursor-move bg-gradient-to-br shadow-xl border',
                        getNodeColor(node),
                        selectedNode?.id === node.id ? 'ring-2 ring-white ring-offset-2 ring-offset-gray-900' : 'border-white/10',
                        connectingFrom === node.id ? 'animate-pulse ring-2 ring-yellow-400' : ''
                    ]"
                    style="min-width: 180px; transition: box-shadow 0.2s, transform 0.1s;"
                    @mousedown="startDrag(node, $event)"
                    @click.stop="selectedNode = node"
                >
                    <!-- Input connector (left) -->
                    <div
                        class="absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-gray-700 border-2 border-indigo-400 rounded-full flex items-center justify-center cursor-pointer hover:bg-indigo-600 hover:scale-110 transition-all"
                        @click="finishConnect(node.id, $event)"
                        title="Connect here"
                    >
                        <span class="text-xs text-white">⬤</span>
                    </div>

                    <!-- Node content -->
                    <div class="text-white font-medium flex items-center">
                        <span class="text-xl mr-2">{{ getNodeIcon(node) }}</span>
                        {{ getNodeLabel(node) }}
                    </div>
                    <div class="text-white/60 text-xs mt-1 font-mono">{{ node.id.slice(-12) }}</div>

                    <!-- Output connector (right) -->
                    <div
                        class="absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-gray-700 border-2 border-green-400 rounded-full flex items-center justify-center cursor-pointer hover:bg-green-600 hover:scale-110 transition-all"
                        @click="startConnect(node.id, $event)"
                        title="Drag to connect"
                    >
                        <span class="text-xs text-white">➤</span>
                    </div>

                    <!-- Delete button -->
                    <button
                        @click.stop="removeNode(node.id)"
                        class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 hover:bg-red-600 text-white rounded-full text-xs shadow-lg transition-colors"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <!-- Config Panel -->
            <div class="w-72 bg-gray-800/80 backdrop-blur p-4 overflow-y-auto border-l border-gray-700">
                <h3 class="text-white font-medium mb-4 text-sm uppercase tracking-wide">Configuration</h3>
                
                <div v-if="selectedNode" class="space-y-4">
                    <div>
                        <label class="text-gray-400 text-xs uppercase">Type</label>
                        <div class="text-white font-medium">{{ getNodeLabel(selectedNode) }}</div>
                    </div>

                    <template v-if="selectedNode.action_type === 'schedule'">
                        <div>
                            <label class="text-gray-400 text-xs uppercase">Cron Expression</label>
                            <input v-model="selectedNode.config.cron" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="0 9 * * *" />
                            <p class="text-gray-500 text-xs mt-1">e.g., 0 9 * * * = 9am daily</p>
                        </div>
                    </template>

                    <template v-if="selectedNode.action_type === 'jerry'">
                        <div>
                            <label class="text-gray-400 text-xs uppercase">Prompt</label>
                            <textarea v-model="selectedNode.config.prompt" rows="4" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-indigo-500 outline-none" />
                        </div>
                    </template>

                    <template v-if="selectedNode.action_type === 'shell'">
                        <div>
                            <label class="text-gray-400 text-xs uppercase">Command</label>
                            <input v-model="selectedNode.config.command" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 mt-1 font-mono focus:ring-2 focus:ring-indigo-500 outline-none" />
                        </div>
                    </template>

                    <template v-if="selectedNode.action_type === 'http'">
                        <div>
                            <label class="text-gray-400 text-xs uppercase">URL</label>
                            <input v-model="selectedNode.config.url" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-indigo-500 outline-none" />
                        </div>
                        <div>
                            <label class="text-gray-400 text-xs uppercase">Method</label>
                            <select v-model="selectedNode.config.method" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-indigo-500 outline-none">
                                <option>GET</option>
                                <option>POST</option>
                                <option>PUT</option>
                                <option>DELETE</option>
                            </select>
                        </div>
                    </template>

                    <template v-if="selectedNode.action_type === 'if_else'">
                        <div>
                            <label class="text-gray-400 text-xs uppercase">Field</label>
                            <input v-model="selectedNode.config.field" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="{{prev_node_id}}" />
                        </div>
                        <div>
                            <label class="text-gray-400 text-xs uppercase">Operator</label>
                            <select v-model="selectedNode.config.operator" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-indigo-500 outline-none">
                                <option value="equals">Equals</option>
                                <option value="not_equals">Not Equals</option>
                                <option value="contains">Contains</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-gray-400 text-xs uppercase">Value</label>
                            <input v-model="selectedNode.config.value" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-indigo-500 outline-none" />
                        </div>
                    </template>
                </div>

                <div v-else class="text-gray-500 text-center py-8">
                    <div class="text-4xl mb-2">👆</div>
                    Select a node to configure
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.workflow-node:hover {
    transform: scale(1.02);
    box-shadow: 0 10px 40px rgba(99, 102, 241, 0.3);
}
</style>
