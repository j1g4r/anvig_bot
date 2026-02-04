<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
  Chart as ChartJS,
  LinearScale,
  PointElement,
  LineElement,
  Tooltip,
  Legend
} from 'chart.js';
import { Scatter } from 'vue-chartjs';
import { computed } from 'vue';

ChartJS.register(LinearScale, PointElement, LineElement, Tooltip, Legend);

const props = defineProps({
    points: Array,
    error: String
});

const chartData = computed(() => {
    // Group by cluster for coloring
    const clusters = {};
    
    props.points.forEach(p => {
        if (!clusters[p.cluster]) clusters[p.cluster] = [];
        clusters[p.cluster].push({
            x: p.x,
            y: p.y,
            title: p.content, // custom data
            id: p.id
        });
    });

    const datasets = Object.keys(clusters).map(key => {
        // Generate random or distinct colors per cluster
        const colors = ['#6366f1', '#ec4899', '#10b981', '#f59e0b', '#3b82f6', '#8b5cf6'];
        const color = colors[key % colors.length];
        
        return {
            label: `Cluster ${key}`,
            fill: false,
            borderColor: color,
            backgroundColor: color,
            data: clusters[key]
        };
    });

    return { datasets };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false // Hide legend for cleaner galaxy look
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    const point = context.raw;
                    return point.title.slice(0, 50) + '...';
                }
            }
        }
    },
    scales: {
        x: {
            grid: { color: '#333333' }
        },
        y: {
            grid: { color: '#333333' }
        }
    },
    onClick: (e, elements) => {
        if (elements.length > 0) {
            // Handle click - maybe show modal details
            // const index = elements[0].index;
            // const datasetIndex = elements[0].datasetIndex;
            // console.log("Clicked memory", index);
        }
    }
};
</script>

<template>
    <Head title="Memory Galaxy" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Memory Galaxy 🌌
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                
                <div v-if="error" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Backend Error:</strong>
                    <span class="block sm:inline">{{ error }}</span>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg h-[80vh] p-4">
                    <div class="h-full w-full relative">
                         <!-- Background Star Effect -->
                         <div class="absolute inset-0 bg-gray-900 pointer-events-none -z-0">
                            <div class="absolute w-1 h-1 bg-white rounded-full top-[10%] left-[20%] opacity-20"></div>
                            <div class="absolute w-2 h-2 bg-purple-500 rounded-full top-[50%] left-[60%] opacity-10 blur-xl"></div>
                         </div>
                         
                        <Scatter v-if="points.length > 0" :data="chartData" :options="chartOptions" class="relative z-10" />
                        
                        <div v-else class="flex items-center justify-center h-full text-gray-500">
                            No memory data mapped yet.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
