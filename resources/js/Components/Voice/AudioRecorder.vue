<script setup>
import { ref, onMounted } from 'vue';
import VoiceService from '@/Services/VoiceService';

const emit = defineEmits(['transcription', 'recording-start', 'recording-stop']);

const isRecording = ref(false);
const isProcessing = ref(false);
let mediaRecorder = null;
let audioChunks = [];

const startRecording = async () => {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        audioChunks = [];

        mediaRecorder.ondataavailable = (event) => {
            audioChunks.push(event.data);
        };

        mediaRecorder.onstop = async () => {
            isProcessing.value = true;
            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
            
            try {
                const result = await VoiceService.transcribe(audioBlob);
                emit('transcription', result.text);
            } catch (error) {
                console.error("Transcription Failed:", error);
            } finally {
                isProcessing.value = false;
                emit('recording-stop');
            }
        };

        mediaRecorder.start();
        isRecording.value = true;
        emit('recording-start');
        
    } catch (error) {
        console.error("Microphone Access Error:", error);
        alert("Couldn't access microphone.");
    }
};

const stopRecording = () => {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
        isRecording.value = false;
    }
};

const toggleRecording = () => {
    if (isRecording.value) {
        stopRecording();
    } else {
        startRecording();
    }
};
</script>

<template>
    <button 
        @click="toggleRecording"
        :disabled="isProcessing"
        class="relative p-2 rounded-full transition-all duration-300 group flex items-center justify-center"
        :class="[
            isRecording ? 'bg-red-500 hover:bg-red-600 scale-110' : 'bg-gray-800 hover:bg-gray-700',
            isProcessing ? 'opacity-50 cursor-wait' : ''
        ]"
    >
        <!-- Pulse Animation -->
        <div v-if="isRecording" class="absolute inset-0 rounded-full bg-red-400 opacity-75 animate-ping"></div>

        <!-- Icon -->
        <svg v-if="!isProcessing" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path v-if="!isRecording" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>

        <!-- Loading Spinner -->
        <svg v-else class="animate-spin h-5 w-5 text-white z-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </button>
</template>
