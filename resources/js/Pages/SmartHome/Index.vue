<script setup>
import { ref, reactive } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    devices: Array,
});

const showAddModal = ref(false);
const form = useForm({
    name: '',
    room: '',
    type: 'light',
    protocol: 'mqtt',
    config: {},
});

// Dynamic config based on protocol
const configTemplates = {
    mqtt: { topic: '', bridge_url: 'http://localhost:8080' },
    http: { base_url: '', headers: {}, endpoints: {} },
    home_assistant: { ha_url: '', token: '', entity_id: '' },
};

const updateConfig = () => {
    form.config = { ...configTemplates[form.protocol] };
};

const addDevice = () => {
    form.post(route('smart-home.store'), {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        },
    });
};

const controlDevice = async (device, action, params = {}) => {
    try {
        const response = await fetch(route('smart-home.control', device.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ action, params }),
        });
        const data = await response.json();
        if (data.success) {
            // Optimistic UI update
            device.state = device.state || {};
            if (action === 'turn_on') device.state.on = true;
            if (action === 'turn_off') device.state.on = false;
            if (action === 'toggle') device.state.on = !device.state.on;
        }
    } catch (e) {
        console.error('Control failed:', e);
    }
};

const deleteDevice = (id) => {
    if (confirm('Remove this device?')) {
        useForm({}).delete(route('smart-home.destroy', id));
    }
};

const deviceIcons = {
    light: '💡',
    switch: '🔌',
    thermostat: '🌡️',
    sensor: '📡',
    lock: '🔒',
    cover: '🚪',
    other: '📦',
};

const groupedDevices = () => {
    const groups = {};
    props.devices.forEach(d => {
        const room = d.room || 'Unassigned';
        if (!groups[room]) groups[room] = [];
        groups[room].push(d);
    });
    return groups;
};
</script>

<template>
    <Head title="Smart Home" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-white leading-tight">
                    🏠 Smart Home
                </h2>
                <button
                    @click="showAddModal = true; updateConfig()"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg"
                >
                    + Add Device
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Rooms Grid -->
                <div v-if="devices.length === 0" class="bg-gray-800 p-8 rounded-lg text-center">
                    <p class="text-gray-400 mb-4">No devices yet. Add your first smart device!</p>
                </div>

                <div v-else class="space-y-6">
                    <div
                        v-for="(roomDevices, room) in groupedDevices()"
                        :key="room"
                        class="bg-gray-800/50 rounded-xl p-4"
                    >
                        <h3 class="text-white font-medium mb-4 flex items-center">
                            <span class="text-xl mr-2">📍</span> {{ room }}
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div
                                v-for="device in roomDevices"
                                :key="device.id"
                                class="bg-gradient-to-br from-gray-700 to-gray-800 rounded-xl p-4 shadow-lg"
                            >
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <span class="text-2xl mr-2">{{ deviceIcons[device.type] }}</span>
                                        <span class="text-white font-medium">{{ device.name }}</span>
                                    </div>
                                    <button
                                        @click="deleteDevice(device.id)"
                                        class="text-red-400 hover:text-red-300 text-sm"
                                    >
                                        ✕
                                    </button>
                                </div>

                                <!-- Status -->
                                <div class="mb-3">
                                    <span
                                        :class="[
                                            'px-2 py-1 rounded text-sm',
                                            device.state?.on ? 'bg-green-600 text-white' : 'bg-gray-600 text-gray-300'
                                        ]"
                                    >
                                        {{ device.state?.on ? '🟢 ON' : '⚪ OFF' }}
                                    </span>
                                    <span v-if="device.is_online" class="ml-2 text-green-400 text-xs">Online</span>
                                    <span v-else class="ml-2 text-gray-500 text-xs">Offline</span>
                                </div>

                                <!-- Controls -->
                                <div class="flex gap-2">
                                    <button
                                        @click="controlDevice(device, 'turn_on')"
                                        class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition-colors"
                                    >
                                        ON
                                    </button>
                                    <button
                                        @click="controlDevice(device, 'turn_off')"
                                        class="flex-1 px-3 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg text-sm transition-colors"
                                    >
                                        OFF
                                    </button>
                                    <button
                                        @click="controlDevice(device, 'toggle')"
                                        class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm transition-colors"
                                    >
                                        ⇄
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tip -->
                <div class="mt-6 p-4 bg-indigo-900/50 border border-indigo-600 rounded-lg">
                    <p class="text-indigo-200">
                        💡 <strong>Tip:</strong> Ask Jerry to control devices!
                        Try: <em>"Turn off the living room lights"</em>
                    </p>
                </div>
            </div>
        </div>

        <!-- Add Device Modal -->
        <div v-if="showAddModal" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50" @click.self="showAddModal = false">
            <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md">
                <h3 class="text-white text-lg font-medium mb-4">Add Smart Device</h3>

                <form @submit.prevent="addDevice" class="space-y-4">
                    <div>
                        <label class="text-gray-400 text-sm">Device Name</label>
                        <input v-model="form.name" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2" placeholder="Living Room Light" />
                    </div>

                    <div>
                        <label class="text-gray-400 text-sm">Room</label>
                        <input v-model="form.room" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2" placeholder="Living Room" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-gray-400 text-sm">Type</label>
                            <select v-model="form.type" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                                <option value="light">💡 Light</option>
                                <option value="switch">🔌 Switch</option>
                                <option value="thermostat">🌡️ Thermostat</option>
                                <option value="sensor">📡 Sensor</option>
                                <option value="lock">🔒 Lock</option>
                                <option value="cover">🚪 Cover</option>
                                <option value="other">📦 Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-gray-400 text-sm">Protocol</label>
                            <select v-model="form.protocol" @change="updateConfig" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                                <option value="mqtt">MQTT</option>
                                <option value="http">HTTP</option>
                                <option value="home_assistant">Home Assistant</option>
                            </select>
                        </div>
                    </div>

                    <!-- Protocol Config -->
                    <div v-if="form.protocol === 'mqtt'">
                        <label class="text-gray-400 text-sm">MQTT Topic</label>
                        <input v-model="form.config.topic" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2" placeholder="zigbee2mqtt/living_room_light" />
                    </div>

                    <div v-if="form.protocol === 'home_assistant'">
                        <label class="text-gray-400 text-sm">Entity ID</label>
                        <input v-model="form.config.entity_id" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2" placeholder="light.living_room" />
                    </div>

                    <div class="flex gap-2 pt-4">
                        <button type="button" @click="showAddModal = false" class="flex-1 px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg" :disabled="form.processing">
                            Add Device
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
