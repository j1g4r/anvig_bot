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
};

const animate = () => {
    frameId = requestAnimationFrame(animate);
    
    controls.update();

    // Rotate Agents
    agentMeshes.forEach(mesh => {
        mesh.rotation.x += 0.01;
        mesh.rotation.y += 0.01;
    });

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
watch(() => props.agents, updateAgents, { deep: true });

</script>

<template>
    <div ref="container" class="w-full h-full"></div>
</template>
