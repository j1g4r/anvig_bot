import axios from 'axios';

class VoiceService {
    constructor() {
        this.audioQueue = []; // Queue for TTS audio blobs
        this.isPlaying = false;
        this.audioElement = new Audio();
        
        this.audioElement.onended = () => {
            this.isPlaying = false;
            this.playNext(); // Play next in queue
        };
    }

    /**
     * Synthesize text to speech
     * @param {string} text 
     * @param {string} voiceId 
     */
    async speak(text, voiceId = null) {
        if (!text) return;
        
        try {
            const response = await axios.post(route('voice.speak'), { text, voice_id: voiceId });
            
            if (response.data.audio) {
                // Convert Base64 to Blob URL
                const byteCharacters = atob(response.data.audio);
                const byteNumbers = new Array(byteCharacters.length);
                for (let i = 0; i < byteCharacters.length; i++) {
                    byteNumbers[i] = byteCharacters.charCodeAt(i);
                }
                const byteArray = new Uint8Array(byteNumbers);
                const blob = new Blob([byteArray], { type: 'audio/mp3' });
                const url = URL.createObjectURL(blob);
                
                this.audioQueue.push(url);
                
                if (!this.isPlaying) {
                    this.playNext();
                }
            }
        } catch (error) {
            console.error("VoiceService Speak Error:", error);
        }
    }

    playNext() {
        if (this.audioQueue.length === 0) return;
        
        const url = this.audioQueue.shift();
        this.audioElement.src = url;
        this.audioElement.play();
        this.isPlaying = true;
    }

    /**
     * Transcribe audio blob
     * @param {Blob} audioBlob 
     */
    async transcribe(audioBlob) {
        const formData = new FormData();
        formData.append('audio', audioBlob, 'recording.webm');
        
        const response = await axios.post(route('voice.transcribe'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        return response.data; // { text: "..." }
    }
    
    stopPlayback() {
        this.audioElement.pause();
        this.audioQueue = [];
        this.isPlaying = false;
    }
}

export default new VoiceService();
