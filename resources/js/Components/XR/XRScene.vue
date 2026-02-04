<template>
  <div class="xr-scene">
    <canvas ref="canvasRef" class="xr-canvas"></canvas>
    <div v-if="!isSessionActive" class="xr-ui">
      <button @click="enterVR" :disabled="!isSupported" class="xr-btn">Enter XR</button>
    </div>
  </div>
</template>
