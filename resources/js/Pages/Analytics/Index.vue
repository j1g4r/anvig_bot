<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement
} from 'chart.js';
import { Bar, Line, Doughnut } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend, PointElement, LineElement, ArcElement);

const props = defineProps(['auth']);

const stats = ref(null);
const loading = ref(true);
const timeRange = ref('24h');

const fetchStats = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/analytics/stats', { params: { range: timeRange.value } });
        stats.value = response.data;
    } catch (e) {
        console.error("Failed to load analytics", e);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchStats();
});

// Chart Data Computed Properties
const agentChartData = computed(() => {
    if (!stats.value) return { labels: [], datasets: [] };
    return {
        labels: stats.value.byAgent.map(a => a.name),
        datasets: [{
            label: 'Tokens Used',
            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
            data: stats.value.byAgent.map(a => a.total)
        }]
    };
});

const timelineChartData = computed(() => {
    if (!stats.value) return { labels: [], datasets: [] };
    return {
        labels: stats.value.timeline.map(t => {
            const date = new Date(t.hour);
            return timeRange.value === '24h' 
                ? date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                : date.toLocaleDateString();
        }),
        datasets: [{
            label: 'Total Tokens',
            backgroundColor: '#8b5cf6',
            borderColor: '#8b5cf6',
            data: stats.value.timeline.map(t => t.total),
            tension: 0.4,
            fill: true
        }]
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { color: '#9ca3af' } },
        title: { display: false }
    },
    scales: {
        y: { grid: { color: '#374151' }, ticks: { color: '#9ca3af' } },
        x: { grid: { display: false }, ticks: { color: '#9ca3af' } }
    }
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'right', labels: { color: '#9ca3af' } }
    },
    cutout: '70%'
};

const formatNumber = (num) => new Intl.NumberFormat().format(num);
</script>

<template>
    <Head title="Analytics" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center px-4">
                <h2 class="text-xl font-black text-gray-800 dark:text-gray-200 uppercase tracking-tighter">
                    📊 Token Analytics
                </h2>
                
                <div class="flex space-x-2 bg-white/5 p-1 rounded-lg">
                    <button v-for="range in ['24h', '7d', '30d']" 
                            :key="range"
                            @click="timeRange = range; fetchStats()"
                            class="px-3 py-1 text-xs font-bold rounded-md transition-colors uppercase tracking-wider"
                            :class="timeRange === range ? 'bg-indigo-500 text-white shadow-lg' : 'text-gray-400 hover:text-white hover:bg-white/10'">
                        {{ range }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6 w-full">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- Loading State -->
                <div v-if="loading" class="flex justify-center py-20">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
                </div>

                <div v-else class="space-y-6 animate-fade-in-up">
                    
                    <!-- KPI Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-gray-800/50 backdrop-blur-xl p-6 rounded-2xl border border-white/5 shadow-xl relative overflow-hidden group hover:border-indigo-500/30 transition-all">
                            <div class="absolute -right-6 -top-6 w-24 h-24 bg-indigo-500/20 rounded-full blur-2xl group-hover:bg-indigo-500/30 transition-all"></div>
                            <h3 class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1">Total Tokens</h3>
                            <p class="text-3xl font-black text-white tracking-tight">{{ formatNumber(stats.totals.total_tokens) }}</p>
                            <div class="mt-2 text-xs font-mono text-indigo-400">Total volume processed</div>
                        </div>

                        <div class="bg-gray-800/50 backdrop-blur-xl p-6 rounded-2xl border border-white/5 shadow-xl relative overflow-hidden group hover:border-emerald-500/30 transition-all">
                            <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/20 rounded-full blur-2xl group-hover:bg-emerald-500/30 transition-all"></div>
                            <h3 class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1">Input Tokens</h3>
                            <p class="text-3xl font-black text-white tracking-tight">{{ formatNumber(stats.totals.total_input) }}</p>
                            <div class="mt-2 text-xs font-mono text-emerald-400">Prompts & Context</div>
                        </div>

                        <div class="bg-gray-800/50 backdrop-blur-xl p-6 rounded-2xl border border-white/5 shadow-xl relative overflow-hidden group hover:border-blue-500/30 transition-all">
                            <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/20 rounded-full blur-2xl group-hover:bg-blue-500/30 transition-all"></div>
                            <h3 class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1">Output Tokens</h3>
                            <p class="text-3xl font-black text-white tracking-tight">{{ formatNumber(stats.totals.total_output) }}</p>
                            <div class="mt-2 text-xs font-mono text-blue-400">AI Generation</div>
                        </div>

                        <div class="bg-gray-800/50 backdrop-blur-xl p-6 rounded-2xl border border-white/5 shadow-xl relative overflow-hidden group hover:border-purple-500/30 transition-all">
                            <div class="absolute -right-6 -top-6 w-24 h-24 bg-purple-500/20 rounded-full blur-2xl group-hover:bg-purple-500/30 transition-all"></div>
                            <h3 class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1">Estimated Cost</h3>
                            <p class="text-3xl font-black text-purple-400 tracking-tight">${{ stats.totals.total_cost_est }}</p>
                            <div class="mt-2 text-xs font-mono text-purple-300">Approximate (GPT-4o-mini rates)</div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Timeline -->
                        <div class="lg:col-span-2 bg-gray-800/50 backdrop-blur-xl p-6 rounded-2xl border border-white/5 shadow-xl">
                            <h3 class="text-white font-bold mb-6 flex items-center">
                                <span class="w-1 h-6 bg-indigo-500 rounded-full mr-3"></span>
                                Usage Trend
                            </h3>
                            <div class="h-64">
                                <Line :data="timelineChartData" :options="chartOptions" />
                            </div>
                        </div>

                        <!-- Agent Distribution -->
                        <div class="bg-gray-800/50 backdrop-blur-xl p-6 rounded-2xl border border-white/5 shadow-xl">
                            <h3 class="text-white font-bold mb-6 flex items-center">
                                <span class="w-1 h-6 bg-emerald-500 rounded-full mr-3"></span>
                                Top Agents
                            </h3>
                            <div class="h-64">
                                <Doughnut :data="agentChartData" :options="doughnutOptions" />
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.5s ease-out;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
