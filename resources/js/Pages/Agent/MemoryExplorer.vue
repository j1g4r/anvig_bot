<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    memories: Object,
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
};

const truncate = (text, length = 100) => {
    if (text.length <= length) return text;
    return text.substring(0, length) + '...';
};
</script>

<template>
    <Head title="Memory Vault" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-black leading-tight text-gray-800 dark:text-gray-200 uppercase tracking-widest">
                    Memory Vault
                </h2>
                <div class="px-4 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-xs font-black rounded-full border border-indigo-200 dark:border-indigo-800/50">
                    Neural Database Storage
                </div>
            </div>
        </template>

        <div class="py-12 relative min-h-screen overflow-hidden">
            <!-- Background Decoration -->
            <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none -z-10">
                <div class="absolute top-1/4 -left-20 w-80 h-80 bg-emerald-500/10 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute bottom-1/4 -right-20 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl"></div>
            </div>

            <div class="mx-auto w-full sm:px-6 lg:px-8">
                <div class="bg-white/60 dark:bg-gray-900/60 backdrop-blur-3xl overflow-hidden shadow-2xl rounded-[2.5rem] border border-white/20 dark:border-gray-800/50">
                    <div class="p-8">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                            <div>
                                <h3 class="text-2xl font-black text-gray-900 dark:text-white">Knowledge Fragments</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Inventory of all historical vector embeddings and contextual chunks.</p>
                            </div>
                            <div class="flex space-x-2">
                                <Link :href="route('agents.index')" class="px-5 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors border border-gray-200 dark:border-gray-700">
                                    Return to HQ
                                </Link>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-3xl border border-gray-100 dark:border-gray-800 shadow-inner bg-white/30 dark:bg-black/20">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50/50 dark:bg-gray-800/50 text-[10px] uppercase font-black tracking-widest text-gray-400">
                                        <th class="px-6 py-4">Status</th>
                                        <th class="px-6 py-4">Content Chunk</th>
                                        <th class="px-6 py-4">Vector Fingerprint</th>
                                        <th class="px-6 py-4">Ingested At</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    <tr v-for="memory in memories.data" :key="memory.id" class="group hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors">
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <div class="flex items-center space-x-2">
                                                <div class="w-2 h-2 rounded-full bg-emerald-500" :class="memory.embedding_binary ? 'shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-orange-500 grayscale opacity-50'"></div>
                                                <span class="text-[10px] font-black text-gray-500 uppercase">{{ memory.embedding_binary ? 'Active' : 'Hybrid' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 text-sm">
                                            <div class="text-gray-900 dark:text-gray-200 leading-relaxed font-medium">
                                                {{ truncate(memory.content, 120) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <code class="text-[10px] bg-gray-100 dark:bg-gray-800 text-gray-500 px-2 py-1 rounded-lg border border-gray-200 dark:border-gray-700 font-mono">
                                                {{ memory.embedding_binary ? 'SHA256_' + memory.id.toString().padStart(6, '0') : 'NULL_POINTER' }}
                                            </code>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap text-xs text-gray-500 font-mono">
                                            {{ formatDate(memory.created_at) }}
                                        </td>
                                    </tr>
                                    <tr v-if="memories.data.length === 0">
                                        <td colspan="4" class="px-6 py-20 text-center">
                                            <div class="text-gray-400 italic">Vault is currently empty. Initiate RAG cycles to populate memory.</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination (Simple) -->
                        <div class="mt-8 flex justify-center space-x-2">
                            <Link v-for="link in memories.links" :key="link.label" 
                                :href="link.url || '#'" 
                                class="px-4 py-2 rounded-xl text-xs font-bold transition-all border"
                                :class="[
                                    link.active ? 'bg-indigo-600 text-white border-indigo-600 shadow-lg shadow-indigo-600/30' : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-gray-100 dark:border-gray-700 hover:border-indigo-500'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.shadow-inner {
    box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.05);
}
</style>
