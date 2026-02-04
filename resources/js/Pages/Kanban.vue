<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    tasks: Array,
    agents: Array,
});

const showCreateModal = ref(false);
const form = useForm({
    title: '',
    description: '',
    agent_id: '',
    priority: 'medium',
});

const createTask = () => {
    form.post(route('kanban.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
};

const updateStatus = (task, status) => {
    router.patch(route('kanban.update', task.id), { status });
};

const runTask = (task) => {
    router.post(route('kanban.run', task.id));
};

const deleteTask = (task) => {
    if (confirm('Are you sure you want to delete this task?')) {
        router.delete(route('kanban.destroy', task.id));
    }
};

const columns = [
    { id: 'todo', name: 'Ready for Mission', class: 'bg-white dark:bg-gray-800 border-indigo-200 dark:border-indigo-900 border-l-4 border-l-indigo-500' },
    { id: 'in_progress', name: 'Active Execution', class: 'bg-white dark:bg-gray-800 border-amber-200 dark:border-amber-900 border-l-4 border-l-amber-500' },
    { id: 'hold', name: 'Evolution Hold', class: 'bg-white dark:bg-gray-800 border-purple-200 dark:border-purple-900 border-l-4 border-l-purple-500' },
    { id: 'done', name: 'Mission Accomplished', class: 'bg-white dark:bg-gray-800 border-emerald-200 dark:border-emerald-900 border-l-4 border-l-emerald-500' },
];


const getAgentName = (agentId) => {
    const agent = props.agents.find(a => a.id === agentId);
    return agent ? agent.name : 'Unassigned';
};

const getPriorityClass = (priority) => {
    switch (priority) {
        case 'high': return 'bg-rose-100 text-rose-800 dark:bg-rose-900 dark:text-rose-100 border-rose-200 dark:border-rose-700';
        case 'medium': return 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100 border-amber-200 dark:border-amber-700';
        case 'low': return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100 border-emerald-200 dark:border-emerald-700';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100 border-gray-200 dark:border-gray-600';
    }
};

import { onMounted, onUnmounted } from 'vue';

const localTasks = ref([...props.tasks]);

// Listen for real-time updates
onMounted(() => {
    window.Echo.channel('kanban')
        .listen('KanbanTaskUpdated', (e) => {
            const index = localTasks.value.findIndex(t => t.id === e.task.id);
            if (index !== -1) {
                // Update existing
                localTasks.value[index] = e.task;
            } else {
                // Add new
                localTasks.value.push(e.task);
            }
        });
});

onUnmounted(() => {
    window.Echo.leave('kanban');
});

const getTasksByStatus = (status) => {
    return localTasks.value.filter(t => t.status === status);
};
</script>

<template>
    <Head title="Kanban Orchestrator" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 18 4.5h-2.25a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 15.75 18.75Zm-2.25-14.25h1.5v1.5h-1.5v-1.5Zm-10.5 0h1.5v1.5h-1.5v-1.5Zm0 12.75h1.5v1.5h-1.5v-1.5Zm0-4.25h1.5v1.5h-1.5v-1.5Zm0-4.25h1.5v1.5h-1.5v-1.5Z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-black text-gray-800 dark:text-gray-200 tracking-tight">Kanban Orchestrator</h2>
                </div>
                <button 
                    @click="showCreateModal = true"
                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold flex items-center gap-2 transition-all shadow-lg shadow-indigo-600/20"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-4 h-4">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Initialize Task
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8">
                <!-- Force horizontal layout with overflow-x-auto for all screen sizes -->
                <div class="flex flex-nowrap gap-6 h-[calc(100vh-16rem)] overflow-x-auto pb-4 custom-scrollbar snap-x snap-mandatory px-1">
                    <div v-for="col in columns" :key="col.id" class="flex-none w-[85vw] md:w-[350px] xl:w-[24%] xl:flex-1 min-w-[300px] flex flex-col bg-gray-50 dark:bg-gray-900/50 rounded-[1rem] border border-gray-200 dark:border-gray-800 p-4 h-full snap-center shadow-sm">
                        <div class="flex items-center justify-between px-4 py-3 mb-4 rounded-lg border-2" :class="col.class">
                            <h3 class="font-black text-xs uppercase tracking-[0.2em] text-gray-800 dark:text-gray-100">
                                {{ col.name }}
                                <span class="ml-2 px-2 py-0.5 bg-gray-200 dark:bg-gray-700 rounded-full text-[10px] text-gray-800 dark:text-gray-100">{{ getTasksByStatus(col.id).length }}</span>
                            </h3>
                        </div>

                        <div class="flex-1 overflow-y-auto px-2 space-y-4 custom-scrollbar-v pr-2">
                            <div v-if="getTasksByStatus(col.id).length === 0" class="border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl p-12 flex flex-col items-center justify-center opacity-40 text-center mt-4">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mb-3 text-gray-500 dark:text-gray-400">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.008 1.24l.33.661a2.25 2.25 0 0 0 2.008 1.24h2.588a2.25 2.25 0 0 0 2.008-1.24l.33-.661a2.25 2.25 0 0 1 2.008-1.24h3.86m-18-10.125a2.25 2.25 0 0 1 2.25-2.25h16.5a2.25 2.25 0 0 1 2.25 2.25v15.75a2.25 2.25 0 0 1-2.25 2.25H4.5a2.25 2.25 0 0 1-2.25-2.25V3.375Z" />
                                </svg>
                                <span class="text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">No Tasks Here</span>
                            </div>

                            <div 
                                v-for="task in getTasksByStatus(col.id)" 
                                :key="task.id"
                                class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all group"
                            >
                                <div class="flex justify-between items-start mb-3">
                                    <div :class="['px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-widest border', getPriorityClass(task.priority)]">
                                        {{ task.priority }}
                                    </div>
                                    <div class="flex gap-1 opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity">
                                        <button @click="deleteTask(task)" class="p-1.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-md">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.244 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <h4 class="text-base font-bold text-gray-900 dark:text-white mb-2 leading-tight tracking-tight">{{ task.title }}</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-300 mb-4 line-clamp-3 leading-relaxed">{{ task.description }}</p>

                                <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100 dark:border-gray-700">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-md bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-bold text-[10px] border border-indigo-100 dark:border-indigo-800">
                                            {{ getAgentName(task.agent_id)[0] }}
                                        </div>
                                        <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">{{ getAgentName(task.agent_id) }}</span>
                                    </div>
                                    <div class="flex gap-1">
                                        <button 
                                            v-if="col.id === 'in_progress'"
                                            @click="updateStatus(task, 'done')"
                                            class="p-2 text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 rounded-lg transition-colors"
                                            title="Complete Task"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-5 h-5">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                        </button>
                                        
                                        <!-- Run button removed for autonomous dispatch -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Task Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-950/80 backdrop-blur-sm" @click="showCreateModal = false"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 w-full max-w-lg shadow-2xl border border-white/10 overflow-hidden">
                <div class="absolute top-0 right-0 p-4">
                    <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-6">Initialize New Mission</h3>

                <form @submit.prevent="createTask" class="space-y-5">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 ml-1">Mission Title</label>
                        <input v-model="form.title" type="text" required class="w-full bg-gray-100 dark:bg-black/40 border-none rounded-2xl px-5 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 ml-1">Briefing / Description</label>
                        <textarea v-model="form.description" rows="3" class="w-full bg-gray-100 dark:bg-black/40 border-none rounded-2xl px-5 py-3 text-sm focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 ml-1">Assign Agent</label>
                            <select v-model="form.agent_id" class="w-full bg-gray-100 dark:bg-black/40 border-none rounded-2xl px-5 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select Agent</option>
                                <option v-for="agent in agents" :key="agent.id" :value="agent.id">{{ agent.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 ml-1">Priority</label>
                            <select v-model="form.priority" class="w-full bg-gray-100 dark:bg-black/40 border-none rounded-2xl px-5 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="w-full py-4 bg-indigo-600 hover:bg-indigo-500 text-white rounded-2xl font-black uppercase tracking-widest transition-all shadow-xl shadow-indigo-600/20 mt-4">
                        Launch Mission
                    </button>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
