<script setup>
/**
 * StreamFrame Component
 * 
 * Renders a single analysed frame with detection overlay.
 * Displays frame description and detected objects list.
 * 
 * @author Vision 2.0 Team
 * @version 2.0.0
 */
import { computed } from 'vue';

const props = defineProps({
    frame: {
        type: Object,
        required: true,
        validator: (value) => {
            return 'frameNumber' in value && 'description' in value;
        }
    },
    isLatest: {
        type: Boolean,
        default: false
    },
    maxObjects: {
        type: Number,
        default: 5
    }
});

const emit = defineEmits(['click']);

// Format timestamp for display
const formattedTime = computed(() => {
    if (!props.frame.timestamp) return '';
    const date = new Date(props.frame.timestamp);
    return date.toLocaleTimeString('en-AU', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
});

// Model badge colour mapping
const modelBadgeClass = computed(() => {
    const model = props.frame.model || 'unknown';
    const classes = {
        'claude-vision': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        'llava': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'unknown': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
    };
    return classes[model] || classes['unknown'];
});

// Truncated object list
const displayedObjects = computed(() => {
    const objects = props.frame.objects || [];
    return objects.slice(0, props.maxObjects);
});

// Has more objects indicator
const hasMoreObjects = computed(() => {
    return (props.frame.objects?.length || 0) > props.maxObjects;
});
</script>

<template>
    <div
        class="stream-frame group relative rounded-lg overflow-hidden bg-gray-900 cursor-pointer transition-transform hover:scale-[1.02]"
        :class="{ 'ring-2 ring-cyan-400': isLatest }"
        @click="emit('click', frame)"
    >
        <!-- Frame Image or Placeholder -->
        <div class="aspect-video relative bg-gray-800">
            <img
                v-if="frame.imageData"
                :src="frame.imageData"
                :alt="`Frame ${frame.frameNumber}`"
                class="w-full h-full object-cover"
                loading="lazy"
            />
            <div
                v-else
                class="w-full h-full flex items-center justify-center text-gray-500"
            >
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>

            <!-- Frame Number Overlay -->
            <div class="absolute top-2 left-2 px-2 py-1 text-xs font-mono bg-black/70 text-white rounded">
                #{{ frame.frameNumber.toString().padStart(4, '0') }}
            </div>

            <!-- Timestamp Overlay -->
            <div
                v-if="formattedTime"
                class="absolute top-2 right-2 px-2 py-1 text-xs font-mono bg-black/70 text-white rounded"
            >
                {{ formattedTime }}
            </div>

            <!-- Model Badge -->
            <div
                class="absolute bottom-2 left-2 px-2 py-0.5 text-xs font-medium rounded"
                :class="modelBadgeClass"
            >
                {{ frame.model || 'Unknown' }}
            </div>

            <!-- Success/Error Indicator -->
            <div
                class="absolute bottom-2 right-2 w-2 h-2 rounded-full"
                :class="frame.success ? 'bg-green-400' : 'bg-red-400'"
                :title="frame.success ? 'Analysis successful' : 'Analysis failed'"
            />
        </div>

        <!-- Description Panel -->
        <div class="p-3 space-y-2">
            <p class="text-sm text-gray-200 line-clamp-2 leading-relaxed">
                {{ frame.description || 'No description available' }}
            </p>

            <!-- Detected Objects -->
            <div v-if="displayedObjects.length > 0" class="flex flex-wrap gap-1">
                <span
                    v-for="object in displayedObjects"
                    :key="object"
                    class="px-2 py-0.5 text-xs bg-cyan-900/50 text-cyan-200 rounded-full border border-cyan-700/50"
                >
                    {{ object }}
                </span>
                <span
                    v-if="hasMoreObjects"
                    class="px-2 py-0.5 text-xs text-gray-400"
                >
                    +{{ frame.objects.length - maxObjects }} more
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
