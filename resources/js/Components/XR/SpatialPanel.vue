<template>
  <div class="spatial-panel" :style="panelStyle">
    <div class="panel-header">
      <h3>{{ title }}</h3>
      <div class="panel-controls">
        <button class="btn-icon" @click="minimise" title="Minimise">
          <span class="icon">−</span>
        </button>
        <button class="btn-icon" @click="maximise" title="Maximise" v-if="!isMaximised">
          <span class="icon">□</span>
        </button>
        <button class="btn-icon" @click="close" title="Close">
          <span class="icon">×</span>
        </button>
      </div>
    </div>
    <div class="panel-content">
      <slot />
    </div>
    <div class="panel-resize-handle" @mousedown="startResize"></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  title: { type: String, default: 'Panel' },
  size: { type: String, default: 'medium' },
  position: { type: Object, default: () => ({ angle: 0, distance: 1.5, height: 0 }) },
  draggable: { type: Boolean, default: true },
  resizable: { type: Boolean, default: true },
  transparent: { type: Boolean, default: true }
});

const emit = defineEmits(['close', 'minimise', 'maximise', 'move']);

const isMaximised = ref(false);
const isMinimised = ref(false);
const currentSize = ref(props.size);
const isResizing = ref(false);

const panelStyle = computed(() => {
  const sizes = { compact: '400px', medium: '600px', wide: '900px', full: '100%' };
  const width = sizes[currentSize.value] || sizes.medium;
  const rad = (props.position.angle * Math.PI) / 180;
  const x = Math.sin(rad) * props.position.distance;
  const z = Math.cos(rad) * props.position.distance;
  const y = props.position.height || 0;

  return {
    width: isMaximised.value ? '100vw' : width,
    height: isMaximised.value ? '100vh' : isMinimised.value ? '48px' : 'auto',
    transform: isMaximised.value 
      ? 'translate3d(0, 0, 0) scale(1)' 
      : `translate3d(${x}m, ${y}m, -${z}m) rotateY(${props.position.angle}deg)`,
    background: props.transparent ? 'rgba(20, 25, 50, 0.75)' : 'rgba(15, 20, 40, 0.95)',
    backdropFilter: props.transparent ? 'blur(20px)' : 'none',
    borderRadius: isMaximised.value ? '0' : '16px',
    border: '1px solid rgba(100, 150, 255, 0.3)',
    boxShadow: '0 8px 32px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1)',
    padding: isMinimised.value ? '8px 16px' : '24px',
    position: isMaximised.value ? 'fixed' : 'absolute',
    top: isMaximised.value ? '0' : 'auto',
    left: isMaximised.value ? '0' : 'auto',
    zIndex: isMaximised.value ? '9999' : '100',
    overflow: 'hidden',
    transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
    pointerEvents: 'auto'
  };
});

const close = () => emit('close');
const minimise = () => {
  isMinimised.value = !isMinimised.value;
  emit('minimise', isMinimised.value);
};
const maximise = () => {
  isMaximised.value = !isMaximised.value;
  emit('maximise', isMaximised.value);
};

const startResize = (e) => {
  if (!props.resizable || isMinimised.value) return;
  isResizing.value = true;
  emit('resize-start');
};

onMounted(() => {
  const handleMouseMove = () => {
    if (isResizing.value) {
      // Handle resize logic here if needed
    }
  };
  
  const handleMouseUp = () => {
    if (isResizing.value) {
      isResizing.value = false;
      emit('resize-end');
    }
  };

  window.addEventListener('mousemove', handleMouseMove);
  window.addEventListener('mouseup', handleMouseUp);

  onUnmounted(() => {
    window.removeEventListener('mousemove', handleMouseMove);
    window.removeEventListener('mouseup', handleMouseUp);
  });
});
</script>

<style scoped>
.spatial-panel {
  color: #e0e6ff;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid rgba(100, 150, 255, 0.2);
}

.panel-header h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #ffffff;
  letter-spacing: 0.5px;
}

.panel-controls {
  display: flex;
  gap: 8px;
}

.btn-icon {
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 6px;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #a8b6ff;
  transition: all 0.2s ease;
}

.btn-icon:hover {
  background: rgba(255, 255, 255, 0.2);
  color: #ffffff;
  transform: scale(1.1);
}

.btn-icon .icon {
  font-size: 14px;
  line-height: 1;
}

.panel-content {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  line-height: 1.6;
  scrollbar-width: thin;
  scrollbar-color: rgba(100, 150, 255, 0.3) transparent;
}

.panel-content::-webkit-scrollbar {
  width: 6px;
}

.panel-content::-webkit-scrollbar-track {
  background: transparent;
}

.panel-content::-webkit-scrollbar-thumb {
  background: rgba(100, 150, 255, 0.3);
  border-radius: 3px;
}

.panel-resize-handle {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 16px;
  height: 16px;
  cursor: se-resize;
  background: linear-gradient(135deg, transparent 50%, rgba(100, 150, 255, 0.3) 50%);
  border-radius: 0 0 16px 0;
  opacity: 0;
  transition: opacity 0.2s;
}

.spatial-panel:hover .panel-resize-handle {
  opacity: 1;
}
</style>
