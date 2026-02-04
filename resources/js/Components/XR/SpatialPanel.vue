<template>
    <div class="panel-header">
    </div>
    </div>
  </div>
  <div class="spatial-panel" :style="panelStyle">
      <h3 ref="titleText">{{ props.title }}</h3>
    <div class="panel-content">
      <slot />
</template>


<script setup>
const Vue = window.Vue || require("vue");
const props = defineProps({
  title: { type: String, default: "Panel" },
  size:  { type: String, default: "medium" },
  position: { type: Object, default: () => ({ angle: 0, distance: 1.5 }) }
const panelStyle = Vue.computed(() => {
  const sizes = { compact: "400px", medium: "600px", wide: "900px" };
  const width = sizes[props.size] || sizes.medium;
  const rad = (props.position.angle * Math.PI) / 180;
  const x = Math.sin(rad) * props.position.distance;
  const z = Math.cos(rad) * props.position.distance;
  return {
    width: width,
    transform: `translate3d(${x}m, 0, -${z}m) rotateY(${props.position.angle}deg)`,
    background: "rgba(255,255,255,0.15)",
    backdropFilter: "blur(16px)",
    borderRadius: "16px",
    border: "1px solid rgba(255,255,255,0.3)",
    boxShadow: "0 8px 32px rgba(0,0,0,0.2)",
    padding: "24px",
    position: "absolute"
  };
});
</script>



<style scoped>
.spatial-panel { color: white; font-family: system-ui, sans-serif; max-height: 80vh; overflow-y: auto; }
.panel-header { margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.2); }
.panel-header h3 { margin: 0; font-size: 1.25rem; font-weight: 600; }
.panel-content { line-height: 1.6; }
</style>

