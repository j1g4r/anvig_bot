<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls';

const props = defineProps({
    agents: Array,
    conversations: Array,
    // Phase 3 Data Props
    systemStats: Object,
    tasks: Array,
    messages: Array,
});

const emits = defineEmits(['agent-select']);

// UI Components
import SystemDashboard from './SystemDashboard.vue';
import CommsPanel from './CommsPanel.vue';
import TaskQueuePanel from './TaskQueuePanel.vue';
import AgentDetailPanel from './AgentDetailPanel.vue';
import PerformancePanel from './PerformancePanel.vue';

// UI State
const leftPanelExpanded = ref(true);
const rightPanelExpanded = ref(true);
const selectedAgent = ref(null);
const showAgentPanel = ref(false);
const showAnalytics = ref(false);

const handleAgentClick = (id) => {
    const agent = props.agents.find(a => a.id === id);
    if (agent) {
        selectedAgent.value = agent;
        showAgentPanel.value = true;
    }
};

const container = ref(null);
let scene, camera, renderer, controls, frameId;
const agentMeshes = [];

// Configuration
const COLORS = {
    agent: 0x3b82f6, // Blue-500
    active: 0x10b981, // Green-500
    idle: 0xf59e0b, // Amber-500
    processing: 0x06b6d4, // Cyan-500
    error: 0xef4444, // Red-500
    offline: 0x64748b, // Slate-500
    grid: 0x1e293b,
    star: 0xffffff,
};

// Mock Status Generator (Phase 1)
const getAgentStatus = (id) => {
    const statuses = ['active', 'idle', 'processing', 'active', 'active'];
    return statuses[id % statuses.length];
};

const getAgentMetrics = () => ({
    cpu: Math.floor(Math.random() * 40) + 20,
    mem: Math.floor(Math.random() * 60) + 30,
    tasks: Math.floor(Math.random() * 5)
});

const init = () => {
    // 1. Scene
    scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0x000000, 0.03);

    // 2. Camera
    const aspect = container.value.clientWidth / container.value.clientHeight;
    camera = new THREE.PerspectiveCamera(60, aspect, 0.1, 1000);
    camera.position.set(0, 5, 10);

    // 3. Renderer
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(window.devicePixelRatio);
    renderer.setSize(container.value.clientWidth, container.value.clientHeight);
    container.value.appendChild(renderer.domElement);

    // 4. Controls
    controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.autoRotate = true;
    controls.autoRotateSpeed = 0.5;

    // 5. Lighting
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
    scene.add(ambientLight);
    
    const pointLight = new THREE.PointLight(0x3b82f6, 2, 50);
    pointLight.position.set(0, 10, 0);
    scene.add(pointLight);

    // 6. Environment
    createStarfield();
    createGrid();

    // 7. Render Agents
    updateAgents();
};

const createAgentNode = (agent, index, count) => {
    const group = new THREE.Group();
    const status = agent.status || getAgentStatus(agent.id);
    const color = COLORS[status] || COLORS.agent;

    // A. Core (Icosahedron)
    const geometry = new THREE.IcosahedronGeometry(0.5, 0);
    const material = new THREE.MeshStandardMaterial({ 
        color: color,
        emissive: color,
        emissiveIntensity: status === 'processing' ? 0.8 : 0.4,
        wireframe: true,
    });
    const core = new THREE.Mesh(geometry, material);
    group.add(core);

    // B. Inner Glow
    const glowGeo = new THREE.IcosahedronGeometry(0.3, 1);
    const glowMat = new THREE.MeshBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.3 });
    const glow = new THREE.Mesh(glowGeo, glowMat);
    group.add(glow);

    // C. Orbit Rings
    const rings = [];
    if (status !== 'offline') {
        const ringCount = status === 'processing' ? 3 : 2;
        
        for (let i = 0; i < ringCount; i++) {
            const radius = 0.7 + (i * 0.2);
            const tube = 0.02;
            const ringGeo = new THREE.TorusGeometry(radius, tube, 8, 32);
            const ringMat = new THREE.MeshBasicMaterial({ color: color, transparent: true, opacity: 0.6 });
            const ring = new THREE.Mesh(ringGeo, ringMat);
            
            // Random orientation
            ring.rotation.x = Math.random() * Math.PI;
            ring.rotation.y = Math.random() * Math.PI;
            
            // Store Rotation Speed dataUserData
            ring.userData = { 
                speedX: (Math.random() - 0.5) * 0.05, 
                speedY: (Math.random() - 0.5) * 0.05 
            };
            
            group.add(ring);
            rings.push(ring);
        }
    }

    // Position
    const angle = (index / count) * Math.PI * 2;
    const radius = 4;
    const x = Math.cos(angle) * radius;
    const z = Math.sin(angle) * radius;
    group.position.set(x, 0, z);

    // Store ref for animation
    group.userData = { id: agent.id, rings: rings, status: status, metrics: getAgentMetrics() };

    return group;
};

const createStarfield = () => {
    const geometry = new THREE.BufferGeometry();
    const count = 3000;
    const vertices = new Float32Array(count * 3);
    for (let i = 0; i < count * 3; i++) {
        vertices[i] = (Math.random() - 0.5) * 100; // Wide spread
    }
    geometry.setAttribute('position', new THREE.BufferAttribute(vertices, 3));
    
    // Varying star sizes/opacity
    const material = new THREE.PointsMaterial({ 
        color: COLORS.star, 
        size: 0.1, 
        transparent: true, 
        opacity: 0.6 
    });
    
    const stars = new THREE.Points(geometry, material);
    scene.add(stars);
};

const createGrid = () => {
    const size = 60;
    const divisions = 60;
    // Main Grid
    const gridHelper = new THREE.GridHelper(size, divisions, COLORS.grid, 0x0f172a);
    gridHelper.position.y = -2; // Below agents
    gridHelper.material.transparent = true;
    gridHelper.material.opacity = 0.15;
    scene.add(gridHelper);
    
    // Secondary "Ground" Plane for depth (Glass effect)
    const planeGeo = new THREE.PlaneGeometry(size, size);
    const planeMat = new THREE.MeshBasicMaterial({ 
        color: 0x000000, 
        transparent: true, 
        opacity: 0.5,
        side: THREE.DoubleSide
    });
    const plane = new THREE.Mesh(planeGeo, planeMat);
    plane.rotation.x = Math.PI / 2;
    plane.position.y = -2.1;
    scene.add(plane);
};

const updateAgents = () => {
    // Clear old meshes
    agentMeshes.forEach(m => scene.remove(m));
    agentMeshes.length = 0;

    const count = props.agents.length;
    
    props.agents.forEach((agent, index) => {
        const group = createAgentNode(agent, index, count);
        scene.add(group);
        agentMeshes.push(group);
    });

    // 8. Render Connections
    updateConnections();
};

const connectionMeshes = [];
const particleSystem = {
    geometry: null,
    material: null,
    mesh: null,
    particles: [], // { position, destination, velocity }
};

const updateConnections = () => {
    // Clear old
    connectionMeshes.forEach(m => scene.remove(m));
    connectionMeshes.length = 0;
    if (particleSystem.mesh) {
        scene.remove(particleSystem.mesh);
        particleSystem.mesh = null;
        particleSystem.particles = [];
    }

    // Map Agent ID to Position
    const agentPositions = {};
    props.agents.forEach((agent, index) => {
        const count = props.agents.length;
        const angle = (index / count) * Math.PI * 2;
        const radius = 4;
        agentPositions[agent.id] = new THREE.Vector3(Math.cos(angle) * radius, 0, Math.sin(angle) * radius);
    });

    // Draw Lines for Active Conversations
    // Logic: Connect HUB (Agent 1) to Current Agent if different.
    // Also connect participants if any.
    
    props.conversations.forEach(conv => {
        const targetId = conv.agent_id;
        const sourceId = 1; // Assuming Central Hub for now (Jerry)

        if (targetId !== sourceId && agentPositions[targetId] && agentPositions[sourceId]) {
            createBeam(agentPositions[sourceId], agentPositions[targetId]);
            createParticles(agentPositions[sourceId], agentPositions[targetId]);
        }
        
        // Connect Participants
        if (conv.participants) {
            conv.participants.forEach(p => {
                if (p.agent_id !== targetId && agentPositions[p.agent_id] && agentPositions[targetId]) {
                    createBeam(agentPositions[targetId], agentPositions[p.agent_id], 0x8b5cf6); // Purple for peer-to-peer
                    createParticles(agentPositions[p.agent_id], agentPositions[targetId]);
                }
            });
        }
    });

    // particle system mesh setup
    if (particleSystem.particles.length > 0) {
        const particleGeo = new THREE.BufferGeometry();
        const positions = new Float32Array(particleSystem.particles.length * 3);
        particleGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        const particleMat = new THREE.PointsMaterial({ color: 0xffffff, size: 0.15, transparent: true, opacity: 0.8 });
        particleSystem.mesh = new THREE.Points(particleGeo, particleMat);
        particleSystem.geometry = particleGeo;
        scene.add(particleSystem.mesh);
    }
};

const createParticles = (start, end) => {
    // Phase 2: "Data Packets" - high density bursts
    const packetCount = Math.floor(Math.random() * 5) + 3; // 3-8 packets per link
    
    for(let i=0; i<packetCount; i++) {
        particleSystem.particles.push({
            current: start.clone(),
            start: start.clone(),
            end: end.clone(),
            progress: Math.random() * 0.2, // Start near the beginning
            speed: 0.01 + Math.random() * 0.02, // Fast movement
            size: Math.random() < 0.2 ? 0.3 : 0.15 // Occasional "Large" packet
        });
    }
};

const createBeam = (v1, v2, color = COLORS.active) => {
    const points = [v1, v2];
    const geometry = new THREE.BufferGeometry().setFromPoints(points);
    
    // Simulate bandwidth with random opacity
    const bandwidth = Math.random(); 
    const opacity = 0.2 + (bandwidth * 0.6); // 0.2 to 0.8
    
    const material = new THREE.LineBasicMaterial({ 
        color: color, 
        transparent: true, 
        opacity: opacity,
        linewidth: 1 // Note: WebGL lines are always 1px on most browsers, glow simulates thickness
    });
    
    const line = new THREE.Line(geometry, material);
    scene.add(line);
    connectionMeshes.push(line);
};


const agentLabels = ref([]);
const connectionLabels = ref([]);

const updateLabels = () => {
    if (!camera || !container.value) return;

    const widthHalf = container.value.clientWidth / 2;
    const heightHalf = container.value.clientHeight / 2;

    // 1. Agent Labels
    const labels = [];
    // Re-map Map Agent ID to Position for connection calculation
    const agentPositions = {};
    
    // Find mesh for updated metrics access
    const findMesh = (id) => agentMeshes.find(m => m.userData.id === id);

    props.agents.forEach((agent, index) => {
        const agentMesh = findMesh(agent.id);
        const count = props.agents.length;
        const angle = (index / count) * Math.PI * 2;
        const radius = 4;
        const x = Math.cos(angle) * radius;
        const z = Math.sin(angle) * radius;
        
        agentPositions[agent.id] = new THREE.Vector3(x, 0, z);

        const pos = new THREE.Vector3(x, 0.6, z); // Slightly above the orb
        pos.project(camera);

        const xPos = (pos.x * widthHalf) + widthHalf;
        const yPos = -(pos.y * heightHalf) + heightHalf;

        if (pos.z < 1) {
            labels.push({
                id: agent.id,
                name: agent.name || 'Agent',
                status: agentMesh?.userData?.status || 'unknown',
                metrics: agentMesh?.userData?.metrics || { cpu: 0, mem: 0 },
                style: {
                    transform: `translate(-50%, -50%) translate(${xPos}px, ${yPos}px)`,
                    position: 'absolute',
                    top: '0',
                    left: '0',
                }
            });
        }
    });
    agentLabels.value = labels;

    // 2. Connection Labels (Tasks)
    const connLabels = [];
    const sourceId = 1; // Assuming Central Hub
    
    props.conversations.forEach(conv => {
        const targetId = conv.agent_id;
        
        if (targetId !== sourceId && agentPositions[targetId] && agentPositions[sourceId]) {
            // Calculate Midpoint
            const mid = new THREE.Vector3().addVectors(agentPositions[sourceId], agentPositions[targetId]).multiplyScalar(0.5);
            mid.y += 0.5; // Lift label slightly

            mid.project(camera);
            
            const xPos = (mid.x * widthHalf) + widthHalf;
            const yPos = -(mid.y * heightHalf) + heightHalf;

            if (mid.z < 1) {
                connLabels.push({
                    id: conv.id,
                    title: `Task #${conv.id}: ${conv.title || 'Untitled'}`,
                    style: {
                        transform: `translate(-50%, -50%) translate(${xPos}px, ${yPos}px)`,
                        position: 'absolute',
                        top: '0',
                        left: '0',
                    }
                });
            }
        }
    });
    connectionLabels.value = connLabels;
};

const animate = () => {
    frameId = requestAnimationFrame(animate);
    
    controls.update();

    // Rotate Agents
    // Rotate Agents and Rings
    agentMeshes.forEach(group => {
        // Rotate the whole group slightly
        group.rotation.y += 0.002;
        
        // Rotate internal rings
        if (group.userData.rings) {
            group.userData.rings.forEach(ring => {
                ring.rotation.x += ring.userData.speedX;
                ring.rotation.y += ring.userData.speedY;
            });
        }
    });

    // Animate Particles
    if (particleSystem.mesh && particleSystem.particles.length > 0) {
        const positions = particleSystem.geometry.attributes.position.array;
        
        particleSystem.particles.forEach((p, i) => {
            p.progress += p.speed;
            if (p.progress >= 1) p.progress = 0;
            
            // Lerp
            p.current.lerpVectors(p.start, p.end, p.progress);
            
            positions[i * 3] = p.current.x;
            positions[i * 3 + 1] = p.current.y;
            positions[i * 3 + 2] = p.current.z;
        });
        
        particleSystem.geometry.attributes.position.needsUpdate = true;
    }

    updateLabels();
    renderer.render(scene, camera);
};

const onResize = () => {
    if (!container.value) return;
    camera.aspect = container.value.clientWidth / container.value.clientHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(container.value.clientWidth, container.value.clientHeight);
};

onMounted(() => {
    init();
    animate();
    window.addEventListener('resize', onResize);
});

onBeforeUnmount(() => {
    cancelAnimationFrame(frameId);
    window.removeEventListener('resize', onResize);
    // Cleanup Three.js resources
    if (renderer) renderer.dispose();
});

// Watch for data changes to re-render nodes
watch(() => [props.agents, props.conversations], () => {
    updateAgents();
    // Connections update is called at the end of updateAgents now
}, { deep: true });

const resetView = () => {
    if (!controls || !camera) return;
    controls.reset();
    camera.position.set(0, 5, 10);
    controls.update();
};

const zoomIn = () => {
    if (!camera) return;
    camera.position.multiplyScalar(0.9);
};

const zoomOut = () => {
    if (!camera) return;
    camera.position.multiplyScalar(1.1);
};

defineExpose({ resetView, zoomIn, zoomOut });
</script>

<template>
    <div class="cortex-command-center relative w-full h-full bg-black overflow-hidden font-mono text-blue-400">
        
        <!-- Layer 1: Canvas Background (Three.js Container) -->
        <div ref="container" class="absolute inset-0 z-0">
             <!-- Three.js appends canvas here -->
        </div>

        <!-- Layer 1b: Projected Labels (Part of 3D Scene relative to camera) -->
        <!-- We keep this overlay logic but it sits "above" the canvas visually -->
        <div class="absolute inset-0 z-10 pointer-events-none overflow-hidden">
            <div v-for="label in agentLabels" 
                 :key="'agent-'+label.id" 
                 :style="label.style"
                 class="pointer-events-none z-10 flex flex-col items-center">
                 
                 <!-- Metrics Badge -->
                 <div class="flex gap-1 mb-1 opacity-0 group-hover:opacity-100 transition-opacity bg-black/80 px-2 py-1 rounded text-[0.6rem] font-mono border border-gray-700">
                    <span class="text-blue-400">CPU:{{ label.metrics.cpu }}%</span>
                    <span class="text-purple-400">MEM:{{ label.metrics.mem }}%</span>
                 </div>

                 <!-- Name Pill -->
                 <div @click="handleAgentClick(label.id)"
                      class="pointer-events-auto px-3 py-1 rounded-full backdrop-blur-md border text-xs font-bold shadow-[0_0_15px_rgba(0,0,0,0.5)] uppercase tracking-wider whitespace-nowrap transition-all flex items-center gap-2 group cursor-pointer hover:scale-110"
                      :class="{
                        'bg-emerald-900/40 border-emerald-500/50 text-emerald-100': label.status === 'active',
                        'bg-amber-900/40 border-amber-500/50 text-amber-100': label.status === 'idle',
                        'bg-cyan-900/40 border-cyan-500/50 text-cyan-100': label.status === 'processing',
                        'bg-red-900/40 border-red-500/50 text-red-100': label.status === 'error',
                        'bg-gray-900/40 border-gray-500/50 text-gray-400': label.status === 'offline',
                      }">
                      <span class="w-2 h-2 rounded-full animate-pulse" 
                            :class="{
                                'bg-emerald-400': label.status === 'active',
                                'bg-amber-400': label.status === 'idle',
                                'bg-cyan-400': label.status === 'processing',
                                'bg-red-400': label.status === 'error',
                                'bg-gray-400': label.status === 'offline',
                            }"></span>
                      {{ label.name }}
                 </div>
            </div>
            
            <div v-for="label in connectionLabels" 
                 :key="'conn-'+label.id" 
                 :style="label.style"
                 class="pointer-events-none px-2 py-0.5 rounded-full bg-emerald-500/20 backdrop-blur-md border border-emerald-500/30 text-[10px] font-mono text-emerald-300 shadow-lg uppercase tracking-tight whitespace-nowrap z-20 transition-opacity flex items-center gap-1">
                 <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                 {{ label.title }}
            </div>
        </div>

        <!-- Layer 2: CRT Effects -->
        <div class="absolute inset-0 z-20 pointer-events-none bg-scanlines opacity-10"></div>
        <div class="absolute inset-0 z-20 pointer-events-none bg-vignette mix-blend-multiply"></div>

        <!-- Layer 3: Dashboard Header -->
        <div class="absolute top-6 left-1/2 -translate-x-1/2 z-30 w-auto pointer-events-none">
            <div class="pointer-events-auto">
                <SystemDashboard :stats="systemStats || {}" />
            </div>
        </div>

        <!-- Layer 4: Left Sidebar (Comms) -->
        <div class="absolute top-24 left-6 bottom-24 z-30 pointer-events-none flex flex-col justify-start items-start">
             <CommsPanel 
                :messages="messages || []" 
                :expanded="leftPanelExpanded" 
                @toggle="leftPanelExpanded = !leftPanelExpanded" 
             />
        </div>

        <!-- Layer 4b: Right Sidebar (Tasks) -->
        <div class="absolute top-24 right-6 bottom-32 z-30 pointer-events-none">
            <div class="pointer-events-auto h-full">
                <!-- We wrap TaskQueuePanel to handle expansion if needed, currently fixed width in its own component design -->
                <TaskQueuePanel :tasks="tasks || []" />
            </div>
        </div>

        <!-- Layer 5: Floating Controls (Bottom Right) -->
        <div class="absolute bottom-6 right-6 z-40 flex flex-col gap-2 pointer-events-auto">
             <div class="flex gap-2 bg-black/80 backdrop-blur-md p-2 rounded-2xl border border-gray-800 shadow-2xl">
                 <button @click="zoomIn" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-800 text-blue-400 hover:bg-blue-600 hover:text-white transition">
                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                 </button>
                 <button @click="zoomOut" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-800 text-blue-400 hover:bg-blue-600 hover:text-white transition">
                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" /></svg>
                 </button>
             </div>
             
             <div class="flex gap-2 bg-black/80 backdrop-blur-md p-2 rounded-2xl border border-gray-800 shadow-2xl">
                 <button @click="resetView" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-800 text-gray-400 hover:bg-gray-700 hover:text-white transition" title="Center View">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" /></svg>
                 </button>
                 <button class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-800 text-gray-400 hover:bg-gray-700 hover:text-white transition" title="Fullscreen">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" /></svg>
                 </button>
             </div>
        </div>

        <!-- Layer 5b: Status Indicator (Bottom Left) -->
        <div class="absolute bottom-6 left-6 z-40 pointer-events-none">
            <div class="bg-black/50 backdrop-blur p-4 rounded-lg border border-gray-800 text-xs">
                <div class="flex gap-8">
                    <div>
                        <span class="block text-gray-500 uppercase">Agents Online</span>
                        <span class="text-2xl font-mono text-blue-400">{{ agents?.length || 0 }}</span>
                    </div>
                    <div>
                         <span class="block text-gray-500 uppercase">Active Links</span>
                        <span class="text-2xl font-mono text-purple-400">{{ conversations?.length || 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Layer 6: Modals -->
        <AgentDetailPanel 
            :agent="selectedAgent || {}" 
            :is-open="showAgentPanel" 
            @close="showAgentPanel = false" 
        />

        <!-- Layer 7: Analytics -->
        <PerformancePanel 
            :is-open="showAnalytics" 
            :agents="agents || []"
            @toggle="showAnalytics = !showAnalytics"
        />

    </div>
</template>
