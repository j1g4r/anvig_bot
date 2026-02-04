<template>
  <div class="xr-space-container">
    <h1>ANVIG XR Space</h1>
    <p class="intro">Spatial computing interface for immersive data visualization</p>
    
    <XRScene />
    
    <div class="info-section">
      <h2>Available Components</h2>
      <ul>
        <li><strong>XRScene</strong> - WebXR entry point with VR toggle</li>
        <li><strong>SpatialPanel</strong> - Glassmorphism UI panels (1.5m distance)</li>
        <li><strong>EntityDetailViewer</strong> - Progressive disclosure for entities</li>
      </ul>
      
      <div class="requirements">
        <h3>System Requirements</h3>
        <p>✓ Chrome 115+ with WebXR flag enabled</p>
        <p>✓ Safari 17.2+ on visionOS (Apple Vision Pro)</p>
        <p>✓ Meta Quest Browser on Quest 3</p>
        <p>✓ Three.js v0.160+ installed</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import XRScene from '../Components/XR/XRScene.vue';
</script>

<style scoped>
.xr-space-container {
  padding: 40px;
  max-width: 1200px;
  margin: 0 auto;
}

h1 {
  font-size: 2.5rem;
  margin-bottom: 8px;
  background: linear-gradient(90deg, #3b82f6, #8b5cf6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.intro {
  color: #6b7280;
  margin-bottom: 40px;
}

.info-section {
  margin-top: 40px;
  padding: 24px;
  background: #f9fafb;
  border-radius: 12px;
}

.info-section h2 {
  font-size: 1.5rem;
  margin-bottom: 16px;
}

.info-section ul {
  margin-left: 24px;
  margin-bottom: 24px;
}

.info-section li {
  margin: 8px 0;
  color: #374151;
}

.requirements {
  background: white;
  padding: 16px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.requirements h3 {
  margin-bottom: 12px;
  color: #111827;
}

.requirements p {
  margin: 8px 0;
  color: #059669;
  font-size: 0.95rem;
}
</style>
