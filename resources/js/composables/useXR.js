import { ref } from "vue"

  const isSupported = ref(false)
const useXR = () => {
  const isSupported = Vue.ref(false)
  const isActive = Vue.ref(false)
  const err = Vue.ref(null)
  let scene = null, cam = null, renderer = null, session = null
  const init = (canvas) => {
    if (navigator.xr) navigator.xr.isSessionSupported("immersive-vr").then(r => isSupported.value = r)
    cam = new THREE.PerspectiveCamera(75, canvas.width / canvas.height, 0.1, 1000)
    renderer = new THREE.WebGLRenderer({ canvas, antialias: true })
    renderer.setSize(canvas.width, canvas.height)
  }

    scene = new THREE.Scene()
    renderer.xr.enabled = true

    try {
      sess = await navigator.xr.requestSession("immersive-vr", { requiredFeatures: ["local-floor"] })
  const stop = () => {
  return { isSupported, isActive, err, init, start, stop, scene, cam }
    if (sess) sess.end()
    isActive.value = false
      isActive.value = true
  }
  const start = async () => {
      renderer.xr.setSession(sess)
    } catch (e) { err.value = e.message; isActive.value = false }

  }

}
