<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    nodes: Array,
});

const showAddModal = ref(false);
const checkingHealth = ref({});

const form = useForm({
    name: '',
    host: 'localhost',
    port: 11434,
});

const addNode = () => {
    form.post(route('ollama.store'), {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        }
    });
};

const deleteNode = (node) => {
    if (confirm(`Remove node "${node.name}"?`)) {
        router.delete(route('ollama.destroy', node.id));
    }
};

const healthCheck = async (node) => {
    checkingHealth.value[node.id] = true;
    router.post(route('ollama.health', node.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            checkingHealth.value[node.id] = false;
        }
    });
};

const healthCheckAll = async () => {
    props.nodes.forEach(n => checkingHealth.value[n.id] = true);
    router.post(route('ollama.health.all'), {}, {
        preserveScroll: true,
        onFinish: () => {
            props.nodes.forEach(n => checkingHealth.value[n.id] = false);
        }
    });
};

const setPrimary = (node) => {
    router.patch(route('ollama.update', node.id), { is_primary: true });
};

const statusColor = (status) => {
    return {
        'online': 'bg-green-500',
        'offline': 'bg-red-500',
        'unknown': 'bg-gray-500',
    }[status] || 'bg-gray-500';
};
</script>

<template>
    <Head title="Ollama Cluster" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-white leading-tight">
                    🖥️ Ollama Cluster
                </h2>
                <div class="flex gap-2">
                    <button
                        @click="healthCheckAll"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-sm"
                    >
                        🔄 Check All
                    </button>
                    <button
                        @click="showAddModal = true"
                        class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg text-sm"
                    >
                        + Add Node
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <!-- Empty State -->
                <div v-if="nodes.length === 0" class="text-center py-16">
                    <div class="text-6xl mb-4">🖥️</div>
                    <h3 class="text-xl text-gray-300 mb-2">No Ollama Nodes</h3>
                    <p class="text-gray-500 mb-6">Add your first Ollama instance to get started.</p>
                    <button
                        @click="showAddModal = true"
                        class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg"
                    >
                        Add First Node
                    </button>
                </div>

                <!-- Nodes Grid -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="node in nodes"
                        :key="node.id"
                        class="bg-gray-800 rounded-xl p-6 border border-gray-700 relative"
                    >
                        <!-- Primary Badge -->
                        <div v-if="node.is_primary" class="absolute top-3 right-3 bg-indigo-600 text-white text-xs px-2 py-1 rounded-full">
                            Primary
                        </div>

                        <!-- Status Indicator -->
                        <div class="flex items-center gap-3 mb-4">
                            <div :class="['w-3 h-3 rounded-full', statusColor(node.status)]"></div>
                            <h3 class="text-white font-medium text-lg">{{ node.name }}</h3>
                        </div>

                        <!-- Details -->
                        <div class="text-sm text-gray-400 space-y-2 mb-4">
                            <div class="flex justify-between">
                                <span>Host:</span>
                                <span class="text-white font-mono">{{ node.host }}:{{ node.port }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Status:</span>
                                <span :class="node.status === 'online' ? 'text-green-400' : 'text-red-400'">
                                    {{ node.status }}
                                </span>
                            </div>
                            <div v-if="node.avg_response_time" class="flex justify-between">
                                <span>Avg Response:</span>
                                <span class="text-white">{{ node.avg_response_time.toFixed(0) }}ms</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Load:</span>
                                <span class="text-white">{{ node.active_requests }}/{{ node.max_concurrent }}</span>
                            </div>
                        </div>

                        <!-- Models -->
                        <div v-if="node.models?.length" class="mb-4">
                            <div class="text-xs text-gray-500 mb-2">Models:</div>
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="model in node.models.slice(0, 5)"
                                    :key="model"
                                    class="text-xs bg-gray-700 text-gray-300 px-2 py-1 rounded"
                                >
                                    {{ model.split(':')[0] }}
                                </span>
                                <span v-if="node.models.length > 5" class="text-xs text-gray-500">
                                    +{{ node.models.length - 5 }} more
                                </span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <button
                                @click="healthCheck(node)"
                                :disabled="checkingHealth[node.id]"
                                class="flex-1 px-3 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded text-sm disabled:opacity-50"
                            >
                                {{ checkingHealth[node.id] ? '...' : '🔄 Check' }}
                            </button>
                            <button
                                v-if="!node.is_primary"
                                @click="setPrimary(node)"
                                class="px-3 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded text-sm"
                            >
                                ⭐ Primary
                            </button>
                            <button
                                @click="deleteNode(node)"
                                class="px-3 py-2 bg-red-600/20 hover:bg-red-600 text-red-400 hover:text-white rounded text-sm"
                            >
                                🗑️
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Node Modal -->
        <div v-if="showAddModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md mx-4">
                <h3 class="text-white text-lg font-medium mb-4">Add Ollama Node</h3>

                <form @submit.prevent="addNode" class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Name</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full bg-gray-700 border-gray-600 rounded-lg text-white"
                            placeholder="My Mac Studio"
                        >
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Host</label>
                        <input
                            v-model="form.host"
                            type="text"
                            class="w-full bg-gray-700 border-gray-600 rounded-lg text-white"
                            placeholder="localhost or 192.168.1.100"
                        >
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Port</label>
                        <input
                            v-model="form.port"
                            type="number"
                            class="w-full bg-gray-700 border-gray-600 rounded-lg text-white"
                        >
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button
                            type="button"
                            @click="showAddModal = false"
                            class="flex-1 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg disabled:opacity-50"
                        >
                            Add Node
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
