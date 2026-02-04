<script setup>
import { ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    services: Array,
});

const form = useForm({
    service_name: '',
    issuer: 'Anvig',
});

const generatedCode = ref(null);
const selectedService = ref(null);

const addService = () => {
    form.post(route('mfa.store'), {
        onSuccess: () => {
            form.reset();
        },
    });
};

const generateCode = async (serviceName) => {
    selectedService.value = serviceName;
    try {
        const response = await fetch(route('mfa.generate'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ service_name: serviceName }),
        });
        const data = await response.json();
        generatedCode.value = data.code;
        setTimeout(() => {
            generatedCode.value = null;
        }, 30000);
    } catch (error) {
        console.error('Failed to generate code:', error);
    }
};

const removeService = (serviceName) => {
    if (confirm(`Remove MFA for ${serviceName}?`)) {
        useForm({ service_name: serviceName }).delete(route('mfa.destroy'));
    }
};
</script>

<template>
    <Head title="MFA Manager" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-white leading-tight">
                🔐 MFA Manager
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Add New Service -->
                <div class="bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-white mb-4">Register New Service</h3>
                    <form @submit.prevent="addService" class="flex gap-4">
                        <input
                            v-model="form.service_name"
                            type="text"
                            placeholder="Service Name (e.g., GitHub)"
                            class="flex-1 bg-gray-700 border-gray-600 text-white rounded-md"
                            required
                        />
                        <input
                            v-model="form.issuer"
                            type="text"
                            placeholder="Issuer"
                            class="w-40 bg-gray-700 border-gray-600 text-white rounded-md"
                        />
                        <button
                            type="submit"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md"
                            :disabled="form.processing"
                        >
                            Add
                        </button>
                    </form>
                </div>

                <!-- Services List -->
                <div class="bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-white mb-4">Registered Services</h3>
                    
                    <div v-if="services.length === 0" class="text-gray-400">
                        No MFA services registered yet.
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="service in services"
                            :key="service"
                            class="flex items-center justify-between p-4 bg-gray-700 rounded-lg"
                        >
                            <span class="text-white font-medium">{{ service }}</span>
                            <div class="flex items-center gap-4">
                                <div v-if="selectedService === service && generatedCode" class="text-2xl font-mono text-green-400">
                                    {{ generatedCode }}
                                </div>
                                <button
                                    @click="generateCode(service)"
                                    class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded"
                                >
                                    Get Code
                                </button>
                                <button
                                    @click="removeService(service)"
                                    class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Flash Message for Secret -->
                <div v-if="$page.props.flash?.secret" class="mt-6 bg-yellow-900 border border-yellow-600 p-4 rounded-lg">
                    <p class="text-yellow-200 font-medium">Save this secret (you won't see it again):</p>
                    <code class="block mt-2 text-yellow-100 font-mono text-lg">{{ $page.props.flash.secret }}</code>
                    <p class="text-yellow-300 mt-2 text-sm">
                        Add to your authenticator app: {{ $page.props.flash.provisioning_uri }}
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
