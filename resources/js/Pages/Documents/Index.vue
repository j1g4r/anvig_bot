<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    documents: Array,
});

const form = useForm({
    file: null,
});

const fileInput = ref(null);

const uploadDocument = () => {
    if (!form.file) return;
    
    form.post(route('documents.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};

const onFileChange = (event) => {
    form.file = event.target.files[0];
};

const deleteDocument = (id) => {
    if (confirm('Delete this document?')) {
        useForm({}).delete(route('documents.destroy', id));
    }
};

const statusColors = {
    pending: 'bg-yellow-600',
    processing: 'bg-blue-600',
    indexed: 'bg-green-600',
    failed: 'bg-red-600',
};
</script>

<template>
    <Head title="Documents" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-white leading-tight">
                📄 Document Repository
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Upload Section -->
                <div class="bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-white mb-4">Upload Document</h3>
                    <form @submit.prevent="uploadDocument" class="flex gap-4 items-center">
                        <input
                            ref="fileInput"
                            type="file"
                            @change="onFileChange"
                            accept=".pdf,.xlsx,.xls,.csv,.txt"
                            class="flex-1 text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-indigo-600 file:text-white hover:file:bg-indigo-700"
                        />
                        <button
                            type="submit"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md disabled:opacity-50"
                            :disabled="form.processing || !form.file"
                        >
                            {{ form.processing ? 'Uploading...' : 'Upload' }}
                        </button>
                    </form>
                    <p class="text-gray-400 text-sm mt-2">
                        Supported: PDF, Excel (.xlsx/.xls), CSV, Text files (max 50MB)
                    </p>
                </div>

                <!-- Documents List -->
                <div class="bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-white mb-4">Your Documents</h3>
                    
                    <div v-if="documents.length === 0" class="text-gray-400">
                        No documents uploaded yet. Upload a file to get started!
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="doc in documents"
                            :key="doc.id"
                            class="flex items-center justify-between p-4 bg-gray-700 rounded-lg"
                        >
                            <div>
                                <div class="text-white font-medium">{{ doc.name }}</div>
                                <div class="text-gray-400 text-sm">
                                    {{ doc.original_filename }} · {{ (doc.size / 1024).toFixed(1) }} KB · {{ doc.chunk_count }} chunks
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <span
                                    :class="[statusColors[doc.status], 'px-2 py-1 rounded text-white text-sm']"
                                >
                                    {{ doc.status }}
                                </span>
                                <button
                                    @click="deleteDocument(doc.id)"
                                    class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Usage Hint -->
                <div class="mt-6 p-4 bg-indigo-900/50 border border-indigo-600 rounded-lg">
                    <p class="text-indigo-200">
                        💡 <strong>Tip:</strong> Once indexed, ask Jerry questions about your documents! 
                        Try: <em>"What does my PDF say about revenue?"</em>
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
