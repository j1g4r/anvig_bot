<template>
  <div ref="panel" class="voice-panel" :style="panelStyle">
    <div class="glass-container">
      <div class="header">
        <span class="title">Voice Control</span>
        <div class="status-dot" :class="{ active: isListening, processing: isProcessing }"></div>
      </div>
      <div class="waveform-container">
        <canvas ref="waveCanvas" class="waveform" width="200" height="60"></canvas>
      </div>
      <div class="transcript" v-if="transcript">{{ transcript }}</div>
      <div class="controls">
        <button @click="toggleRec" class="btn-main" :class="{ recording: isListening }">
          {{ isListening ? "Stop" : "Listen" }}
        </button>
      </div>
      <div class="commands">
        <span class="cmd" @click="emitCmd('select')">Select [name]</span>
        <span class="cmd" @click="emitCmd('expand')">Expand</span>
        <span class="cmd" @click="emitCmd('back')">Back</span>
        <span class="cmd" @click="emitCmd('new')">New Session</span>
      </div>
    </div>
  </div>
</template>

<script>
const XRVoicePanel = {
  name: 'XRVoicePanel',
  props: {
    position: { type: Object, default: () => ({ x: 0, y: -0.4, z: -1.5 }) }
  },
  emits: ['voice-command'],
  setup(props, { emit }) {
    const { rec, proc, trans, audLvl, initAudio, startRec, stopRec } = useVoice()
    const waveCanvas = Vue.ref(null)
    const panel = Vue.ref(null)
    const animId = Vue.ref(null)

    const isListening = Vue.computed(() => rec.value)
    const isProcessing = Vue.computed(() => proc.value)
    const transcript = Vue.computed(() => trans.value)

    const panelStyle = Vue.computed(() => ({
      transform: `translate3d(${props.position.x}m, ${props.position.y}m, ${props.position.z}m)`
    }))

    const drawWave = () => {
      const cvs = waveCanvas.value
      if (!cvs) return
      const ctx = cvs.getContext('2d')
      const w = cvs.width
      const h = cvs.height
      ctx.fillStyle = 'rgba(0,0,0,0.3)'
      ctx.fillRect(0, 0, w, h)
      const lvl = audLvl.value
      ctx.strokeStyle = isListening.value ? '#00ff88' : '#00aaff'
      ctx.lineWidth = 2
      ctx.beginPath()
      for (let x = 0; x < w; x += 2) {
        const amp = lvl * (h / 2) * (0.5 + 0.5 * Math.sin((x / w) * Math.PI))
        const y = (h / 2) + (x % 4 === 0 ? 1 : -1) * amp * Math.random()
        if (x === 0) ctx.moveTo(x, y)
        else ctx.lineTo(x, y)
      }
      ctx.stroke()
    }

    const animLoop = () => {
      if (isListening.value) drawWave()
      animId.value = requestAnimationFrame(animLoop)
    }

    const toggleRec = async () => {
      if (!isListening.value) {
        await initAudio()
        startRec()
        animLoop()
      } else {
        stopRec()
        if (animId.value) cancelAnimationFrame(animId.value)
      }
    }

    const emitCmd = (cmd) => { emit('voice-command', cmd) }

    Vue.onMounted(() => { animLoop() })
    Vue.onUnmounted(() => {
      if (animId.value) cancelAnimationFrame(animId.value)
    })

    return { 
      panel, waveCanvas, 
      isListening, isProcessing, transcript, 
      panelStyle, toggleRec, emitCmd 
    }
  }
}
</script>

<style scoped>
.voice-panel {
  position: absolute;
  width: 280px;
  pointer-events: auto;
}
.glass-container {
  background: rgba(20, 25, 50, 0.6);
  backdrop-filter: blur(16px);
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  padding: 16px;
}
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.title { font-weight: 600; color: #e0e6ff; font-size: 14px; }
.status-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #666;
  transition: background 0.3s;
}
.status-dot.active { background: #00ff88; box-shadow: 0 0 8px #00ff88; }
.status-dot.processing { background: #ffaa00; animation: pulse 1s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

.waveform-container {
  height: 60px;
  background: rgba(0, 0, 0, 0.4);
  border-radius: 8px;
  margin-bottom: 12px;
  overflow: hidden;
}
.waveform { width: 100%; height: 100%; display: block; }

.transcript {
  font-size: 13px;
  color: #a8b6ff;
  min-height: 20px;
  margin-bottom: 12px;
  word-break: break-word;
  padding: 8px;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 6px;
}

.controls { text-align: center; margin-bottom: 12px; }
.btn-main {
  background: linear-gradient(135deg, #0077ff, #6600ff);
  color: white;
  border: none;
  padding: 10px 28px;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}
.btn-main:hover { transform: scale(1.05); box-shadow: 0 4px 20px rgba(0,119,255,0.4); }
.btn-main.recording { background: linear-gradient(135deg, #ff3333, #ff6600); animation: recording-pulse 1.5s infinite; }
@keyframes recording-pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(255,51,51,0.4); } 50% { box-shadow: 0 0 20px 10px rgba(255,51,51,0.2); } }

.commands {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6px;
}
.cmd {
  font-size: 11px;
  padding: 6px 8px;
  background: rgba(255,255,255,0.1);
  border-radius: 6px;
  text-align: center;
  cursor: pointer;
  color: #a8b6ff;
  transition: all 0.2s;
}
.cmd:hover { background: rgba(255,255,255,0.2); color: white; }
</style>
