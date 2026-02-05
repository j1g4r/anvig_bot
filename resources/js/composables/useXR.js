import { ref, onMounted, onUnmounted } from 'vue';
import * as THREE from 'three';

/**
 * WebXR Composable - Manages immersive VR/AR sessions with Three.js
 * Provides reactive state for XR support, active session tracking, and scene management
 */
export function useXR() {
    // Reactive state
    const isSupported = ref(false);
    const isActive = ref(false);
    const error = ref(null);
    const sessionMode = ref('immersive-vr'); // 'immersive-vr' or 'immersive-ar'

    // Three.js internals (non-reactive for performance)
    let scene = null;
    let camera = null;
    let renderer = null;
    let xrSession = null;
    let animationFrameId = null;
    let referenceSpace = null;

    /**
     * Check WebXR support for the requested session mode
     */
    const checkSupport = async (mode = 'immersive-vr') => {
        if (!navigator.xr) {
            isSupported.value = false;
            return false;
        }
        try {
            const supported = await navigator.xr.isSessionSupported(mode);
            isSupported.value = supported;
            sessionMode.value = mode;
            return supported;
        } catch (e) {
            error.value = `XR support check failed: ${e.message}`;
            isSupported.value = false;
            return false;
        }
    };

    /**
     * Initialize Three.js scene and renderer
     */
    const init = (canvas, options = {}) => {
        if (!canvas) {
            error.value = 'Canvas element required for XR initialization';
            return false;
        }

        try {
            // Scene setup
            scene = new THREE.Scene();
            scene.background = new THREE.Color(options.backgroundColor || 0x0a0a1a);

            // Camera setup
            const aspect = canvas.width / canvas.height || 1;
            camera = new THREE.PerspectiveCamera(75, aspect, 0.1, 1000);
            camera.position.set(0, 1.6, 3); // Standing height

            // Renderer setup
            renderer = new THREE.WebGLRenderer({
                canvas,
                antialias: true,
                alpha: true
            });
            renderer.setSize(canvas.width || window.innerWidth, canvas.height || window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            renderer.xr.enabled = true;

            // Add default lighting
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
            scene.add(ambientLight);

            const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
            directionalLight.position.set(0, 10, 0);
            scene.add(directionalLight);

            // Check XR support
            checkSupport(options.mode || 'immersive-vr');

            return true;
        } catch (e) {
            error.value = `XR Init failed: ${e.message}`;
            return false;
        }
    };

    /**
     * Start XR session
     */
    const start = async (options = {}) => {
        if (!navigator.xr) {
            error.value = 'WebXR not supported on this device';
            return false;
        }

        if (!renderer) {
            error.value = 'Renderer not initialized. Call init() first.';
            return false;
        }

        const mode = options.mode || sessionMode.value || 'immersive-vr';
        const features = options.requiredFeatures || ['local-floor'];

        try {
            xrSession = await navigator.xr.requestSession(mode, {
                requiredFeatures: features
            });

            // Set up reference space
            referenceSpace = await xrSession.requestReferenceSpace('local-floor');

            // Configure renderer for XR
            renderer.xr.setReferenceSpace(referenceSpace);
            await renderer.xr.setSession(xrSession);

            // Session event handlers
            xrSession.addEventListener('end', onSessionEnd);

            // Start render loop
            isActive.value = true;
            error.value = null;

            renderer.setAnimationLoop(render);

            return true;
        } catch (e) {
            error.value = `Failed to start XR session: ${e.message}`;
            isActive.value = false;
            return false;
        }
    };

    /**
     * Stop XR session
     */
    const stop = async () => {
        if (xrSession) {
            try {
                await xrSession.end();
            } catch (e) {
                console.warn('Error ending XR session:', e);
            }
        }
        onSessionEnd();
    };

    /**
     * Handle session end (called on manual stop or device exit)
     */
    const onSessionEnd = () => {
        isActive.value = false;
        if (renderer) {
            renderer.setAnimationLoop(null);
        }
        xrSession = null;
        referenceSpace = null;
    };

    /**
     * Render loop
     */
    const render = () => {
        if (renderer && scene && camera) {
            renderer.render(scene, camera);
        }
    };

    /**
     * Add object to scene
     */
    const addToScene = (object) => {
        if (scene) {
            scene.add(object);
            return true;
        }
        return false;
    };

    /**
     * Remove object from scene
     */
    const removeFromScene = (object) => {
        if (scene) {
            scene.remove(object);
            return true;
        }
        return false;
    };

    /**
     * Get current camera position
     */
    const getCameraPosition = () => {
        return camera ? camera.position.clone() : null;
    };

    /**
     * Clean up resources
     */
    const dispose = () => {
        stop();
        
        if (renderer) {
            renderer.dispose();
            renderer = null;
        }
        
        scene = null;
        camera = null;
    };

    // Cleanup on unmount
    onUnmounted(() => {
        dispose();
    });

    return {
        // State
        isSupported,
        isActive,
        error,
        sessionMode,
        
        // Scene access
        scene: () => scene,
        camera: () => camera,
        renderer: () => renderer,
        
        // Methods
        init,
        start,
        stop,
        addToScene,
        removeFromScene,
        getCameraPosition,
        checkSupport,
        dispose
    };
}

export default useXR;
