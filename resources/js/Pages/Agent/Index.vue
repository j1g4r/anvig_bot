<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    agents: Array,
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

            <div class="mx-auto w-full sm:px-6 lg:px-8">
                <div class="mb-10 flex justify-between items-end">
                    <div>
                        <h3 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-500">
                            Operational Intelligence
                        </h3>
                        <p class="text-gray-500 dark:text-gray-400">Select an agent unit to initiate command and control.</p>
                    </div>
                    <Link :href="route('agents.vault')" class="px-6 py-3 bg-white/50 dark:bg-gray-800/50 backdrop-blur-xl border border-gray-200 dark:border-gray-700 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:border-indigo-500 transition-all flex items-center space-x-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-indigo-500">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9s2.015-9 4.5-9m0 0c.815 0 1.583.333 2.147.886M12 3c-.815 0-1.583.333-2.147.886" />
                        </svg>
                        <span>Memory Vault</span>
                    </Link>
                </div>

                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="agent in agents" :key="agent.id" 
                        class="relative overflow-hidden bg-white/50 dark:bg-gray-800/50 backdrop-blur-xl shadow-2xl rounded-3xl p-1 group transition-all duration-500 hover:-translate-y-2 border border-gray-100 dark:border-gray-700 hover:border-indigo-500/50">
                        
                        <!-- Animated Gradient Border on Hover -->
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/20 to-purple-500/20 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                        <div class="relative bg-white dark:bg-gray-900 rounded-[22px] p-8 h-full flex flex-col">
                            <div class="flex items-center space-x-4 mb-6">
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-700 flex items-center justify-center text-white shadow-lg shadow-indigo-500/40 transform group-hover:rotate-12 transition-transform duration-500">
                                    <span class="text-2xl font-black">{{ agent.name.charAt(0) }}</span>
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <h3 class="text-xl font-black text-gray-900 dark:text-white">{{ agent.name }}</h3>
                                        <button @click="openEditModal(agent)" class="text-gray-400 hover:text-indigo-500 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281Z" />
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="flex items-center space-x-1 text-xs text-indigo-500 font-bold uppercase tracking-widest mt-1">
                                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                        <span>Systems Ready</span>
                                    </div>
                                </div>
                            </div>

                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-8 flex-1 leading-relaxed">
                                Advanced autonomous unit powered by <span class="text-gray-800 dark:text-gray-200 font-mono font-bold">{{ agent.model }}</span>. 
                                Ready for complex task execution and long-term memory retrieval.
                            </p>

                            <div class="flex justify-between items-center mt-auto pt-6 border-t border-gray-100 dark:border-gray-800">
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-tighter">
                                    {{ agent.conversations_count }} Active Logs
                                </div>
                                <Link :href="route('agents.show', agent.id)" 
                                    class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all duration-300 shadow-[0_10px_20px_-5px_rgba(79,70,229,0.5)] active:scale-95">
                                    Initialize
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

