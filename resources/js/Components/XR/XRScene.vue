<template>
  <div ref="sceneContainer" class="xr-scene">
    <canvas ref="canvasRef" class="xr-canvas"></canvas>
    <div v-if="!isActive" class="xr-ui">
      <button @click="enterVR" :disabled="!isSupported" class="xr-btn">
        {{ isSupported ? "Enter XR Space" : "WebXR Not Supported" }}
      </button>
      <p class="support-msg" v-if="!isSupported">Use Quest Browser or Chrome on Android</p>
    </div>
    <XRVoicePanel v-if="isActive" @voice-command="onVoiceCmd" :position="{ x: 0, y: -0.4, z: -1.5 }" />
  </div>
</template>

<script>
const XRScene = {
  name: 'XRScene',
  components: { XRVoicePanel },
  setup() {
    const canvasRef = Vue.ref(null)
    const sceneContainer = Vue.ref(null)
    const xr = useXR()
    const isSupported = Vue.computed(() => xr.isSupported.value)
    const isActive = Vue.computed(() => xr.isActive.value)
    const error = Vue.computed(() => xr.error.value)

    const enterVR = async () => {
      if (!canvasRef.value) return
      xr.init(canvasRef.value)
      await xr.start()
    }

    const onVoiceCmd = (cmd) => {
      console.log('Voice command received:', cmd)
      if (cmd === 'new') {
        xr.stop()
      }
    }

    return {
      canvasRef,
      sceneContainer,
      isSupported,
      isActive,
      error,
      enterVR,
      onVoiceCmd
    }
  }
}
</script>

<style scoped>
.xr-scene {
  width: 100%;
  height: 100vh;
  position: relative;
  background: linear-gradient(135deg, #0a0a1a, #1a1a3a);
  overflow: hidden;
}

.xr-canvas {
  width: 100%;
  height: 100%;
  display: block;
}

.xr-ui {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  z-index: 10;
}

.xr-btn {
  background: linear-gradient(135deg, #00aaff, #0066ff);
  color: white;
  border: none;
  padding: 16px 32px;
  font-size: 18px;
  font-weight: 600;
  border-radius: 30px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 20px rgba(0, 119, 255, 0.4);
}

.xr-btn:hover:not(:disabled) {
  transform: scale(1.05);
  box-shadow: 0 6px 30px rgba(0, 119, 255, 0.6);
}

.xr-btn:disabled {
  background: linear-gradient(135deg, #666, #444);
  cursor: not-allowed;
  opacity: 0.7;
}

.support-msg {
  color: #888;
  font-size: 14px;
  margin-top: 12px;
}
</style>
