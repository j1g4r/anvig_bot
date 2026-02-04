<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    navItems: Object,
    navVisibility: Object,
});

const form = useForm({
    visibility: { ...props.navVisibility },
});

const save = () => {
    form.post(route('settings.nav-visibility'));
};

const toggleAll = (value) => {
    Object.keys(form.visibility).forEach(key => {
        if (!props.navItems[key]?.locked) {
            form.visibility[key] = value;
        }
    });
};
</script>

<template>
    <Head title="Settings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-white leading-tight">
                ⚙️ Settings
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <!-- Navigation Visibility -->
                <div class="bg-gray-800 rounded-xl p-6 shadow-lg">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-white font-medium text-lg">Navigation Menu</h3>
                            <p class="text-gray-400 text-sm">Choose which items appear in your navigation bar</p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                @click="toggleAll(true)"
                                class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-sm"
                            >
                                Show All
                            </button>
                            <button
                                @click="toggleAll(false)"
                                class="px-3 py-1 bg-gray-600 hover:bg-gray-500 text-white rounded text-sm"
                            >
                                Hide All
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <label
                            v-for="(item, key) in navItems"
                            :key="key"
                            :class="[
                                'flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors',
                                form.visibility[key] ? 'bg-indigo-600/30 border border-indigo-500' : 'bg-gray-700/50 border border-gray-600',
                                item.locked ? 'opacity-60 cursor-not-allowed' : 'hover:bg-gray-700'
                            ]"
                        >
                            <input
                                type="checkbox"
                                v-model="form.visibility[key]"
                                :disabled="item.locked"
                                class="w-4 h-4 text-indigo-600 bg-gray-700 border-gray-600 rounded focus:ring-indigo-500"
                            />
                            <span class="text-xl">{{ item.icon }}</span>
                            <span class="text-white">{{ item.label }}</span>
                            <span v-if="item.locked" class="text-gray-500 text-xs ml-auto">Required</span>
                        </label>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button
                            @click="save"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white rounded-lg shadow-lg transition-all"
                        >
                            {{ form.processing ? 'Saving...' : 'Save Settings' }}
                        </button>
                    </div>
                </div>

                <!-- Tip -->
                <div class="mt-6 p-4 bg-indigo-900/50 border border-indigo-600 rounded-lg">
                    <p class="text-indigo-200">
                        💡 <strong>Tip:</strong> Dashboard and Agents are always visible. Other items can be hidden to simplify your navigation.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
