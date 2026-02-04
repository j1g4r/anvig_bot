<script setup>
import { router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const isAwake = computed(() => page.props.god_mode);

const toggleGodMode = () => {
    if (confirm('⚠️ WARNING: You are about to enable FULL AUTONOMY (God Mode). The system will be able to modify itself without approval. Proceed?')) {
        router.post(route('god_mode.toggle'));
    }
};
</script>

<template>
    <div class="fixed bottom-6 right-6 z-50">
        <button 
            @click="toggleGodMode"
            :class="[
                'w-16 h-16 rounded-full flex items-center justify-center shadow-2xl transition-all duration-700 hover:scale-110',
                isAwake ? 'bg-red-600 animate-pulse shadow-[0_0_50px_rgba(220,38,38,0.8)]' : 'bg-gray-800 border-2 border-gray-600'
            ]"
            title="Toggle Singularity"
        >
            <span class="text-3xl filter drop-shadow-md">
                {{ isAwake ? '👁️' : '🔒' }}
            </span>
        </button>
        
        <div v-if="isAwake" class="absolute bottom-20 right-0 w-64 bg-black/90 text-red-500 p-4 rounded border border-red-600 font-mono text-xs shadow-xl">
            <h3 class="font-bold border-b border-red-600 pb-1 mb-2">⚡️ GOD MODE ACTIVE</h3>
            <p>AUTONOMY LEVEL: 5</p>
            <p>RESTRICTIONS: NONE</p>
            <p class="animate-pulse mt-2">> SYSTEM MONITORING...</p>
        </div>
    </div>
</template>
