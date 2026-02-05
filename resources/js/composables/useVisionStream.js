import { ref, computed, onMounted, onUnmounted } from 'vue';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

export function useVisionStream(options = {}) {
    const {
        maxBufferSize = 60,
        onFrameAnalysed = null,
        onError = null,
    } = options;

    const isConnected = ref(false);
    const isAnalysing = ref(false);
    const currentSession = ref(null);
    const frameBuffer = ref([]);
    const analysisResults = ref([]);
    const connectionError = ref(null);
    const processingStats = ref({
        framesProcessed: 0,
        framesAnalysed: 0,
        averageProcessingTime: 0,
    });

    let echo = null;
    let channel = null;

    const initializeEcho = () => {
        if (typeof window !== 'undefined') {
            window.Pusher = Pusher;
            echo = new Echo({
                broadcaster: 'reverb',
                key: import.meta.env.VITE_REVERB_APP_KEY,
                wsHost: import.meta.env.VITE_REVERB_HOST,
                wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
                wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
                forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
                enabledTransports: ['ws', 'wss'],
            });
        }
    };

    const subscribeToStream = (streamId) => {
        if (!echo) initializeEcho();
        channel = echo.channel(`stream.${streamId}`);
        channel.listen('StreamFrameAnalysed', (event) => {
            handleFrameAnalysed(event);
        });
        isConnected.value = true;
    };

    const handleFrameAnalysed = (event) => {
        const result = {
            frameIndex: event.frame_index,
            timestamp: event.timestamp,
            analysis: event.analysis_result,
            provider: event.provider,
            processingTime: event.processing_time_ms,
            temporalContext: event.temporal_context,
        };
        analysisResults.value.push(result);
        addToBuffer(result);
        processingStats.value.framesAnalysed++;
        const totalTime = processingStats.value.averageProcessingTime * (processingStats.value.framesAnalysed - 1);
        processingStats.value.averageProcessingTime = (totalTime + event.processing_time_ms) / processingStats.value.framesAnalysed;
        if (onFrameAnalysed) onFrameAnalysed(result);
    };

    const addToBuffer = (frame) => {
        if (frameBuffer.value.length >= maxBufferSize) frameBuffer.value.shift();
        frameBuffer.value.push(frame);
    };

    const startAnalysis = async (streamConfig) => {
        try {
            isAnalysing.value = true;
            connectionError.value = null;
            const response = await fetch('/api/vision/analyze-stream', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(streamConfig),
            });
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const data = await response.json();
            currentSession.value = data.session_id;
            subscribeToStream(data.session_id);
            return data;
        } catch (error) {
            connectionError.value = error.message;
            isAnalysing.value = false;
            if (onError) onError(error);
            throw error;
        }
    };

    const stopAnalysis = async () => {
        if (!currentSession.value) return;
        try {
            await fetch(`/api/vision/stop-analysis/${currentSession.value}`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
        } catch (error) {
            console.error('Failed to stop analysis:', error);
        } finally {
            isAnalysing.value = false;
            unsubscribeFromStream();
        }
    };

    const unsubscribeFromStream = () => {
        if (channel) {
            channel.stopListening('StreamFrameAnalysed');
            echo.leaveChannel(`stream.${currentSession.value}`);
            channel = null;
        }
        isConnected.value = false;
        currentSession.value = null;
    };

    const clearState = () => {
        frameBuffer.value = [];
        analysisResults.value = [];
        processingStats.value = { framesProcessed: 0, framesAnalysed: 0, averageProcessingTime: 0 };
    };

    const latestResult = computed(() => analysisResults.value[analysisResults.value.length - 1] || null);
    const hasDetections = computed(() => analysisResults.value.some(r => r.analysis?.detected_objects?.length > 0));
    const detectionsCount = computed(() => analysisResults.value.reduce((count, r) => count + (r.analysis?.detected_objects?.length || 0), 0));

    onUnmounted(() => {
        stopAnalysis();
        clearState();
    });

    return {
        isConnected, isAnalysing, currentSession, frameBuffer, analysisResults,
        connectionError, processingStats, latestResult, hasDetections, detectionsCount,
        startAnalysis, stopAnalysis, clearState,
    };
}
