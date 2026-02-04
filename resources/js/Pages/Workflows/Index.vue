<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    workflows: Array,
});

const runWorkflow = async (id) => {
    try {
        const response = await fetch(route('workflows.run', id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        const data = await response.json();
        alert(`Workflow ${data.status}!\nLogs: ${JSON.stringify(data.logs, null, 2)}`);
    } catch (e) {
        alert('Failed to run workflow');
    }
};

const deleteWorkflow = (id) => {
    if (confirm('Delete this workflow?')) {
        useForm({}).delete(route('workflows.destroy', id));
    }
};
</script>

<template>
    <Head title="Workflows" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-white leading-tight">
                    ⚙️ Workflow Builder
                </h2>
                <Link
                    :href="route('workflows.create')"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md"
                >
                    + New Workflow
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="workflows.length === 0" class="bg-gray-800 p-8 rounded-lg text-center">
                    <p class="text-gray-400 mb-4">No workflows yet. Create your first automation!</p>
                    <Link
                        :href="route('workflows.create')"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md"
                    >
                        Create Workflow
                    </Link>
                </div>

                <div v-else class="grid gap-4">
                    <div
                        v-for="workflow in workflows"
                        :key="workflow.id"
                        class="bg-gray-800 p-6 rounded-lg flex items-center justify-between"
                    >
                        <div>
                            <h3 class="text-white font-medium text-lg">{{ workflow.name }}</h3>
                            <p class="text-gray-400 text-sm">
                                {{ workflow.nodes_count }} nodes · 
                                Last run: {{ workflow.last_run_at || 'Never' }}
                            </p>
                            <p v-if="workflow.description" class="text-gray-500 text-sm mt-1">
                                {{ workflow.description }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                @click="runWorkflow(workflow.id)"
                                class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded"
                            >
                                ▶ Run
                            </button>
                            <Link
                                :href="route('workflows.edit', workflow.id)"
                                class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded"
                            >
                                Edit
                            </Link>
                            <button
                                @click="deleteWorkflow(workflow.id)"
                                class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
