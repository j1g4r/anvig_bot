<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls';

const props = defineProps({
    agents: Array,
    conversations: Array,
});

const container = ref(null);
let scene, camera, renderer, controls, frameId;
const agentMeshes = [];

// Configuration
const COLORS = {
    agent: 0x3b82f6, // Blue-500
    active: 0x10b981, // Green-500
    grid: 0x1e293b,
    star: 0xffffff,
};

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

const createStarfield = () => {
    const geometry = new THREE.BufferGeometry();
    const count = 2000;
    const positions = new Float32Array(count * 3);

    for (let i = 0; i < count * 3; i++) {
        positions[i] = (Math.random() - 0.5) * 100;
    }

    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    const material = new THREE.PointsMaterial({ size: 0.1, color: COLORS.star });
    const stars = new THREE.Points(geometry, material);
    scene.add(stars);
};

const createGrid = () => {
    const grid = new THREE.GridHelper(50, 50, COLORS.active, COLORS.grid);
    grid.position.y = -2;
    // Fade out grid logic could be added with shaders, keeping it simple for now
    scene.add(grid);
};

const updateAgents = () => {
    // Clear old meshes
    agentMeshes.forEach(m => scene.remove(m));
    agentMeshes.length = 0;

    const count = props.agents.length;
    
    props.agents.forEach((agent, index) => {
        // Arrange in circle
        const angle = (index / count) * Math.PI * 2;
        const radius = 4;
        const x = Math.cos(angle) * radius;
        const z = Math.sin(angle) * radius;

        // Geometry: Icosahedron (Techy Look)
        const geometry = new THREE.IcosahedronGeometry(0.5, 0);
        const material = new THREE.MeshStandardMaterial({ 
            color: COLORS.agent,
            emissive: COLORS.agent,
            emissiveIntensity: 0.5,
            wireframe: true,
        });
        const mesh = new THREE.Mesh(geometry, material);
        mesh.position.set(x, 0, z);

        // Add Label (HTML Overlay or TextGeometry - skipping specifically for brevity, just orb for now)
        
        // Add Pulse Effect (Inner Glow)
        const coreGeo = new THREE.IcosahedronGeometry(0.3, 1);
        const coreMat = new THREE.MeshBasicMaterial({ color: 0xffffff });
        const core = new THREE.Mesh(coreGeo, coreMat);
        mesh.add(core);

        scene.add(mesh);
        agentMeshes.push(mesh);
    });

    // 8. Render Connections (The Neural Links)
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

const createBeam = (v1, v2, color = COLORS.active) => {
    const points = [v1, v2];
    const geometry = new THREE.BufferGeometry().setFromPoints(points);
    const material = new THREE.LineBasicMaterial({ color: color, transparent: true, opacity: 0.4 });
    const line = new THREE.Line(geometry, material);
    scene.add(line);
    connectionMeshes.push(line);
};

const createParticles = (start, end) => {
    // Add a few particles per connection
    for(let i=0; i<3; i++) {
        particleSystem.particles.push({
            current: start.clone(),
            start: start.clone(),
            end: end.clone(),
            progress: Math.random(), // Randomized start
            speed: 0.005 + Math.random() * 0.01
        });
    }
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

    props.agents.forEach((agent, index) => {
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
    agentMeshes.forEach(mesh => {
        mesh.rotation.x += 0.01;
        mesh.rotation.y += 0.01;
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

</script>

<template>
    <div ref="container" class="w-full h-full relative overflow-hidden">
        <div v-for="label in agentLabels" 
             :key="'agent-'+label.id" 
             :style="label.style"
             class="pointer-events-none px-3 py-1 rounded-full bg-black/40 backdrop-blur-md border border-white/10 text-xs font-bold text-white shadow-lg uppercase tracking-wider whitespace-nowrap z-10 transition-opacity">
             {{ label.name }}
        </div>
        
        <div v-for="label in connectionLabels" 
             :key="'conn-'+label.id" 
             :style="label.style"
             class="pointer-events-none px-2 py-0.5 rounded-full bg-emerald-500/20 backdrop-blur-md border border-emerald-500/30 text-[10px] font-mono text-emerald-300 shadow-lg uppercase tracking-tight whitespace-nowrap z-20 transition-opacity flex items-center gap-1">
             <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
             {{ label.title }}
        </div>
    </div>
</template>
