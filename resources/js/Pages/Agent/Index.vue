<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    agents: Array,
});

const searchQuery = ref('');

import { computed } from 'vue';

const filteredAgents = computed(() => {
    if (!searchQuery.value) return props.agents;
    const q = searchQuery.value.toLowerCase();
    
    return props.agents.filter(agent => {
        return agent.name.toLowerCase().includes(q) 
            || (agent.model && agent.model.toLowerCase().includes(q))
            || (agent.personality && agent.personality.toLowerCase().includes(q));
    });
});

const showEditModal = ref(false);
const editingAgent = ref(null);

const form = useForm({
    name: '',
    model: '',
    personality: '',
});

const openEditModal = (agent) => {
    editingAgent.value = agent;
    form.name = agent.name;
    form.model = agent.model;
    form.personality = agent.personality || ''; // Handle null
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    form.reset();
    editingAgent.value = null;
};

const updateAgent = () => {
    form.patch(route('agents.update', editingAgent.value.id), {
        onSuccess: () => closeEditModal(),
    });
};
</script>

<template>
    <Head title="Agents" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                My Agents
            </h2>
        </template>

        <div class="relative py-12 min-h-[calc(100vh-8rem)]">
            <!-- Background Decoration -->
            <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none -z-10">
                <div class="absolute -top-24 -left-24 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute top-1/2 -right-24 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl"></div>
            </div>

            <!-- Search & Controls -->
            <div class="mx-auto w-full sm:px-6 lg:px-8 mb-8">
                <div class="flex flex-col md:flex-row justify-between items-end gap-4">
                    <div>
                        <h3 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-500">
                            Operational Intelligence
                        </h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">Select an agent unit to initiate command and control.</p>
                    </div>
                    
                    <div class="flex gap-4 w-full md:w-auto items-center">
                        <!-- Search Bar -->
                        <div class="relative w-full md:w-64">
                            <TextInput 
                                v-model="searchQuery" 
                                placeholder="Search agents..." 
                                class="w-full bg-white/50 dark:bg-gray-800/50 backdrop-blur border-none text-sm"
                            />
                            <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>

                        <Link :href="route('agents.vault')" class="px-4 py-2 bg-white/50 dark:bg-gray-800/50 backdrop-blur-xl border border-gray-200 dark:border-gray-700 rounded-xl text-[10px] font-black uppercase tracking-widest hover:border-indigo-500 transition-all flex items-center space-x-2 whitespace-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-indigo-500">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9s2.015-9 4.5-9m0 0c.815 0 1.583.333 2.147.886M12 3c-.815 0-1.583.333-2.147.886" />
                            </svg>
                            <span>Memories</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Compact Grid -->
            <div class="mx-auto w-full sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="agent in filteredAgents" :key="agent.id" 
                        class="relative group bg-white dark:bg-gray-900 overflow-hidden rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-lg hover:border-indigo-500/30 transition-all duration-200">
                        
                        <!-- Hover Gradient -->
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-purple-500/5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>

                        <div class="p-5 flex flex-col h-full">
                            <!-- Header -->
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-600 to-purple-700 flex items-center justify-center text-white shadow-md">
                                        <span class="text-lg font-black">{{ agent.name.charAt(0) }}</span>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-900 dark:text-white text-base leading-tight">{{ agent.name }}</h3>
                                        <span class="text-[10px] font-mono text-gray-500 uppercase tracking-wide">{{ agent.model }}</span>
                                    </div>
                                </div>
                                <button @click="openEditModal(agent)" class="text-gray-400 hover:text-indigo-500 p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                </button>
                            </div>
                            
                            <!-- Tools / Tags -->
                            <div class="mb-4 flex flex-wrap gap-1">
                                <span v-if="agent.tools_config && agent.tools_config.length" class="px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-300 text-[10px] font-medium border border-indigo-100 dark:border-indigo-800/50">
                                    {{ agent.tools_config.length }} tools
                                </span>
                                <span class="px-2 py-0.5 rounded-md bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-300 text-[10px] font-medium border border-green-100 dark:border-green-800/50 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> Active
                                </span>
                            </div>

                            <!-- Footer -->
                            <div class="mt-auto pt-3 border-t border-gray-50 dark:border-gray-800 flex justify-between items-center">
                                <span class="text-[10px] text-gray-400 font-medium">{{ agent.conversations_count }} logs</span>
                                <Link :href="route('agents.show', agent.id)" 
                                    class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 hover:underline uppercase tracking-wider">
                                    Connect &rarr;
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <Modal :show="showEditModal" @close="closeEditModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Configure Agent: {{ editingAgent?.name }}
                </h2>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Update the agent's identity and operational parameters.
                </p>

                <div class="mt-6">
                    <div class="mb-4">
                        <InputLabel for="name" value="Name" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Agent Name"
                        />
                    </div>
                    
                    <div class="mb-4">
                        <InputLabel for="model" value="Model" />
                        <TextInput
                            id="model"
                            v-model="form.model"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="E.g. gpt-4o, llama3"
                        />
                    </div>

                    <div class="mb-4">
                        <InputLabel for="personality" value="Personality / System Vibe" />
                        <textarea
                            id="personality"
                            v-model="form.personality"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm h-32"
                            placeholder="Describe how this agent should behave (e.g., 'You are a snarky pirate', 'You are a formal academic')."
                        ></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeEditModal"> Cancel </SecondaryButton>

                    <PrimaryButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="updateAgent"
                    >
                        Save Configuration
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

