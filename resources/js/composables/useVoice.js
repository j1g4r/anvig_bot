const useVoice = () => {
  const rec = Vue.ref(false)
  const proc = Vue.ref(false)
  const trans = Vue.ref("")
  const audLvl = Vue.ref(0)
  const err = Vue.ref(null)
  let ctx = null
  let analyser = null
  let dataArr = null
  let mediaRec = null
  let audioChunks = []
  let stream = null
  let lvlIntv = null

  const stopIntv = () => {
    if (lvlIntv) { clearInterval(lvlIntv); lvlIntv = null }
  }

  const updLvl = () => {
    if (!analyser || !dataArr) return
    analyser.getByteFrequencyData(dataArr)
    const sum = dataArr.reduce((a, b) => a + b, 0)
    audLvl.value = sum / dataArr.length / 255
  }

  const initAudio = async () => {
    try {
      stream = await navigator.mediaDevices.getUserMedia({ audio: true })
      ctx = new (window.AudioContext || window.webkitAudioContext)()
      const src = ctx.createMediaStreamSource(stream)
      analyser = ctx.createAnalyser()
      analyser.fftSize = 256
      dataArr = new Uint8Array(analyser.frequencyBinCount)
      src.connect(analyser)
    } catch (e) { err.value = "Mic denied: " + e.message }
  }

  const startRec = () => {
    if (!stream) { err.value = "Init audio first"; return }
    audioChunks = []
    const mime = MediaRecorder.isTypeSupported("audio/webm") ? "audio/webm" : "audio/mp4"
    mediaRec = new MediaRecorder(stream, { mimeType: mime })
    mediaRec.ondataavailable = evt => { if (evt.data.size > 0) audioChunks.push(evt.data) }
    mediaRec.onstop = () => {
      const blob = new Blob(audioChunks, { type: mime })
      procAudio(blob)
      stopIntv()
    }
    mediaRec.start()
    rec.value = true
    lvlIntv = setInterval(updLvl, 50)
  }

  const stopRec = () => {
    if (mediaRec && mediaRec.state !== "inactive") mediaRec.stop()
    rec.value = false
    stopIntv()
  }

  const procAudio = async (blob) => {
    proc.value = true
    const formData = new FormData()
    formData.append("audio", blob, "voice.webm")
    try {
      const res = await fetch("/api/voice/upload", { method: "POST", body: formData })
      const data = await res.json()
      trans.value = data.text || ""
      audLvl.value = 0
    } catch (e) { err.value = "API error: " + e.message }
    proc.value = false
  }

  return { rec, proc, trans, audLvl, err, initAudio, startRec, stopRec }
}
