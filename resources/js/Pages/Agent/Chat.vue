<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, nextTick, watch, computed } from 'vue';
import MarkdownIt from 'markdown-it';
import hljs from 'highlight.js';
import 'highlight.js/styles/github-dark.css';

const md = new MarkdownIt({
    html: true,
    linkify: true,
    typographer: true,
    highlight: function (str, lang) {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return hljs.highlight(str, { language: lang }).value;
            } catch (__) {}
        }
        return ''; 
    }
});

import ChatMessage from '@/Components/ChatMessage.vue';

const props = defineProps({
    agent: Object,
    conversation: Object,
    allAgents: Array,
});

const showThoughts = ref(localStorage.getItem('jerry_show_thoughts') !== 'false');
const showCanvas = ref(false);
const canvas = ref(props.conversation.canvas || null);

const toggleThoughts = () => {
    showThoughts.value = !showThoughts.value;
    localStorage.setItem('jerry_show_thoughts', showThoughts.value);
};

const toggleCanvas = () => {
    showCanvas.value = !showCanvas.value;
};

const form = useForm({
    message: '',
    image: null,
});

const imagePreview = ref(null);

// @-mention autocomplete
const showMentionPopup = ref(false);
const mentionFilter = ref('');
const mentionIndex = ref(0);
const inputRef = ref(null);

const mentionableAgents = computed(() => {
    const agents = [
        props.agent,
        ...(props.allAgents || []),
        ...(props.conversation.participants?.map(p => p.agent) || []),
    ];
    // Dedupe by id
    const unique = [...new Map(agents.filter(Boolean).map(a => [a.id, a])).values()];
    if (!mentionFilter.value) return unique;
    return unique.filter(a => a.name.toLowerCase().includes(mentionFilter.value.toLowerCase()));
});

const handleInput = (e) => {
    const value = e.target.value;
    const cursorPos = e.target.selectionStart;
    const textBeforeCursor = value.slice(0, cursorPos);
    const atMatch = textBeforeCursor.match(/@(\w*)$/);
    
    if (atMatch) {
        showMentionPopup.value = true;
        mentionFilter.value = atMatch[1];
        mentionIndex.value = 0;
    } else {
        showMentionPopup.value = false;
        mentionFilter.value = '';
    }
};

const selectMention = (agent) => {
    const value = form.message;
    const cursorPos = inputRef.value?.selectionStart || value.length;
    const textBeforeCursor = value.slice(0, cursorPos);
    const textAfterCursor = value.slice(cursorPos);
    const newBefore = textBeforeCursor.replace(/@\w*$/, `@${agent.name} `);
    form.message = newBefore + textAfterCursor;
    showMentionPopup.value = false;
    mentionFilter.value = '';
    nextTick(() => inputRef.value?.focus());
};

const handleMentionKeydown = (e) => {
    if (!showMentionPopup.value) return;
    
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        mentionIndex.value = Math.min(mentionIndex.value + 1, mentionableAgents.value.length - 1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        mentionIndex.value = Math.max(mentionIndex.value - 1, 0);
    } else if (e.key === 'Enter' && mentionableAgents.value.length > 0) {
        e.preventDefault();
        selectMention(mentionableAgents.value[mentionIndex.value]);
    } else if (e.key === 'Escape') {
        showMentionPopup.value = false;
    }
};

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    
    form.image = file;
    
    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const clearImage = () => {
    form.image = null;
    imagePreview.value = null;
};

const messagesContainer = ref(null);
const isThinking = ref(false);
const ttsEnabled = ref(localStorage.getItem('tts_enabled') === 'true');

// Audio setup
const playSound = (type) => {
    const sounds = {
        sent: 'https://assets.mixkit.co/active_storage/sfx/2354/2354-preview.mp3',
        received: 'https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3'
    };
    const audio = new Audio(sounds[type]);
    audio.volume = 0.3;
    audio.play().catch(() => {}); // Browser might block until interaction
};

// Speech setup
const speak = (text) => {
    if (!ttsEnabled.value) return;
    
    // Clean text (remove tool call brackets etc)
    const cleanText = text.replace(/\[.*?\]/g, '').trim();
    if (!cleanText) return;

    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(cleanText);
    utterance.rate = 1.0;
    utterance.pitch = 1.0;
    window.speechSynthesis.speak(utterance);
};

const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const submit = () => {
    if (!form.message.trim()) return;
    
    isThinking.value = true;
    playSound('sent');
    
    form.post(route('agents.chat', [props.agent.id, props.conversation.id]), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            clearImage();
            nextTick(scrollToBottom);
        },
        onError: () => {
            isThinking.value = false;
        }
    });
};

const isRecording = ref(false);
const recognition = ref(null);

const initRecognition = () => {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        console.warn("Speech recognition not supported in this browser.");
        return null;
    }

    const rec = new SpeechRecognition();
    rec.continuous = true;
    rec.interimResults = true;
    rec.lang = 'en-US';

    rec.onresult = (event) => {
        let interimTranscript = '';
        let finalTranscript = '';

        for (let i = event.resultIndex; i < event.results.length; ++i) {
            if (event.results[i].isFinal) {
                finalTranscript += event.results[i][0].transcript;
            } else {
                interimTranscript += event.results[i][0].transcript;
            }
        }
        
        // Append transcribed text as it comes in
        if (finalTranscript) {
            form.message = (form.message + ' ' + finalTranscript).trim();
        }
    };

    rec.onend = () => {
        isRecording.value = false;
    };

    rec.onerror = (event) => {
        console.error("Speech Recognition Error:", event.error);
        isRecording.value = false;
    };

    return rec;
};

const startRecording = () => {
    if (!recognition.value) {
        recognition.value = initRecognition();
    }
    
    if (recognition.value && !isRecording.value) {
        try {
            recognition.value.start();
            isRecording.value = true;
            playSound('sent'); // Feedback
        } catch (e) {
            console.error("Failed to start recognition:", e);
        }
    }
};

const stopRecording = () => {
    if (recognition.value && isRecording.value) {
        recognition.value.stop();
        isRecording.value = false;
    }
};

onMounted(() => {
    setupScrollObserver();
    recognition.value = initRecognition();
    
    // Real-time updates via Echo
    if (window.Echo) {
        const channel = window.Echo.channel(`chat.${props.conversation.id}`);
        
        channel.listen('MessageCreated', (e) => {
            const exists = props.conversation.messages.find(m => m.id === e.message.id);
            if (!exists) {
                props.conversation.messages.push(e.message);
                
                if (e.message.role === 'assistant' || e.message.role === 'tool') {
                    isThinking.value = false;
                    if (e.message.role === 'assistant') {
                        playSound('received');
                        speak(e.message.content);
                    }
                }
                
                nextTick(scrollToBottom);
            }
        })
        .listen('AgentThinking', (e) => {
            isThinking.value = true;
        })
        .listen('.CanvasUpdated', (e) => {
            canvas.value = e.canvas;
            if (!showCanvas.value) showCanvas.value = true;
        });
    }
});

const toggleTts = () => {
    ttsEnabled.value = !ttsEnabled.value;
    localStorage.setItem('tts_enabled', ttsEnabled.value);
    if (!ttsEnabled.value) window.speechSynthesis.cancel();
};

watch(() => props.conversation.messages, () => {
    nextTick(scrollToBottom);
}, { deep: true });

const setupScrollObserver = () => {
    if (!messagesContainer.value) return;
    const observer = new ResizeObserver(() => {
        scrollToBottom();
    });
    observer.observe(messagesContainer.value);
    setTimeout(scrollToBottom, 100);
};
</script>

<template>
    <Head :title="agent.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-black shadow-lg shadow-indigo-500/30">J</div>
                    <div>
                        <h2 class="text-xl font-black leading-tight text-gray-800 dark:text-gray-200">
                            {{ agent.name }}
                        </h2>
                        <div class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest">{{ agent.model }}</div>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button @click="toggleTts" class="p-2 rounded-lg transition-colors" :class="ttsEnabled ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30' : 'text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'">
                        <svg v-if="ttsEnabled" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.75 19.5 12m0 0 2.25 2.25M19.5 12l2.25-2.25M19.5 12l-2.25 2.25m-10.5-6 4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z" />
                        </svg>
                    </button>

                    <!-- Reasoning Toggle -->
                    <button 
                        @click="toggleThoughts"
                        class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider transition-all"
                        :class="showThoughts 
                            ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-500/20' 
                            : 'bg-gray-100 dark:bg-gray-800 text-gray-500 border border-gray-200 dark:border-gray-700'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        Neural Flow: {{ showThoughts ? 'Active' : 'Muted' }}
                    </button>
                    <div class="flex items-center space-x-2">
                        <button 
                            @click="toggleCanvas"
                            class="p-2 rounded-lg transition-colors"
                            :class="showCanvas ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30' : 'text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'"
                            title="Toggle Workspace"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
                            </svg>
                        </button>

                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest bg-white/50 dark:bg-gray-800/50 px-3 py-1 rounded-full border border-gray-100 dark:border-gray-700 shadow-sm">{{ conversation.title }}</div>
                        <a :href="route('agents.export', [agent.id, conversation.id])" class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-white dark:hover:bg-gray-800 rounded-lg transition-all" title="Export Chat">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </template>

        <div class="flex flex-col h-[calc(100vh-10rem)] sm:h-[calc(100vh-10rem)] w-full mx-auto px-2 sm:px-6 lg:px-8 py-2 sm:py-6">
            <div class="flex-1 flex flex-col sm:flex-row gap-6 overflow-hidden relative">
                <!-- Chat Panel -->
                <div class="flex-1 flex flex-col min-w-0">
                    <div class="flex-1 overflow-y-auto bg-white/40 dark:bg-gray-950/40 backdrop-blur-2xl rounded-[2.5rem] p-4 sm:p-8 space-y-6 border border-white/20 dark:border-gray-800/50 shadow-[0_32px_64px_-16px_rgba(0,0,0,0.2)] custom-scrollbar-v relative" ref="messagesContainer">
                        <!-- Inner Mesh Gradient for Depth -->
                        <div class="absolute inset-0 pointer-events-none opacity-20 dark:opacity-40 -z-10">
                            <div class="absolute top-0 -left-1/4 w-full h-full bg-gradient-to-br from-indigo-500/20 via-transparent to-transparent blur-3xl"></div>
                        </div>

                        <div v-if="conversation.messages.length === 0" class="flex flex-col items-center justify-center h-full text-center space-y-6 opacity-40">
                            <div class="w-24 h-24 rounded-[2rem] bg-gradient-to-br from-indigo-600 to-purple-700 animate-pulse flex items-center justify-center shadow-2xl shadow-indigo-500/50">
                                <span class="text-5xl font-black text-white">J</span>
                            </div>
                            <div>
                                <div class="text-2xl font-black text-gray-900 dark:text-white mb-2">Systems Online</div>
                                <div class="text-sm max-w-xs mx-auto text-gray-500 dark:text-gray-400">Autonomous unit {{ agent.name }} initialized on local network. Awaiting command sequence.</div>
                            </div>
                        </div>
                        
                        <div v-for="msg in conversation.messages" :key="msg.id" class="flex flex-col space-y-3">
                            <ChatMessage :msg="msg" :show-thoughts="showThoughts" />
                        </div>

                        <!-- Thinking Indicator -->
                        <div v-if="isThinking" class="flex justify-start animate-fade-in pb-4">
                            <div class="bg-indigo-50/50 dark:bg-indigo-950/20 backdrop-blur-sm rounded-[1.2rem] px-5 py-3 border border-indigo-500/10">
                                <div class="flex items-center space-x-3">
                                    <div class="flex space-x-1">
                                        <div class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce [animation-delay:-0.3s]"></div>
                                        <div class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce [animation-delay:-0.15s]"></div>
                                        <div class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce"></div>
                                    </div>
                                    <span class="text-[9px] font-black text-indigo-500/70 uppercase tracking-[0.2em]">Neural Processing</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Canvas Side Panel -->
                <transition 
                    enter-active-class="transition ease-out duration-300 transform"
                    enter-from-class="translate-x-full opacity-0"
                    enter-to-class="translate-x-0 opacity-100"
                    leave-active-class="transition ease-in duration-200 transform"
                    leave-from-class="translate-x-0 opacity-100"
                    leave-to-class="translate-x-full opacity-0"
                >
                    <div v-if="showCanvas" class="fixed inset-0 z-50 sm:z-0 sm:relative w-full sm:w-[500px] flex flex-col bg-white/95 dark:bg-gray-950/95 sm:bg-white/60 sm:dark:bg-gray-900/60 backdrop-blur-3xl border border-white/20 dark:border-gray-800/50 sm:rounded-[2.5rem] shadow-2xl overflow-hidden min-w-0">
                        <div class="p-6 border-b border-white/10 flex justify-between items-center bg-gradient-to-r from-emerald-500/10 to-transparent">
                            <div>
                                <h3 class="font-black text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-emerald-500">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
                                    </svg>
                                    {{ canvas?.title || 'Workspace' }}
                                </h3>
                                <div class="text-[8px] font-black uppercase tracking-widest text-emerald-500/60 mt-1">Live Artifact Workspace • v{{ canvas?.version || 1 }}</div>
                            </div>
                            <button @click="toggleCanvas" class="text-gray-400 hover:text-gray-600 dark:hover:text-white p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="flex-1 overflow-y-auto p-8 custom-scrollbar-v">
                            <div v-if="!canvas" class="flex flex-col items-center justify-center h-full text-center space-y-4 opacity-30 grayscale">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-16 h-16">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <span class="text-xs uppercase tracking-widest font-black">No Active Artifact</span>
                            </div>
                            <div v-else class="text-sm">
                                <template v-if="canvas.type === 'code'">
                                     <pre class="p-4 bg-gray-950 rounded-2xl border border-white/5 overflow-x-auto text-emerald-400 font-mono text-xs leading-relaxed">{{ canvas.content }}</pre>
                                </template>
                                <div v-else class="prose dark:prose-invert max-w-none text-gray-800 dark:text-gray-200" v-html="md.render(canvas.content)"></div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- Input Area -->
            <div class="mt-4 sm:mt-8 bg-white/60 dark:bg-gray-900/60 backdrop-blur-3xl p-3 sm:p-5 border border-white/20 dark:border-gray-800/50 rounded-[2.5rem] shadow-2xl z-10 mx-1">
                <form @submit.prevent="submit" class="flex flex-col space-y-3">
                    <!-- Image Preview -->
                    <div v-if="imagePreview" class="relative group inline-block">
                         <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-2xl overflow-hidden border-2 border-indigo-500 p-1 bg-white dark:bg-gray-800">
                             <img :src="imagePreview" class="w-full h-full object-cover rounded-xl">
                         </div>
                         <button @click="clearImage" type="button" class="absolute -top-3 -right-3 bg-red-500 text-white rounded-full w-7 h-7 flex items-center justify-center text-sm shadow-lg hover:bg-red-600 transition-colors">×</button>
                    </div>

                    <div class="flex items-center space-x-3">
                        <label class="cursor-pointer flex items-center justify-center p-3 text-gray-400 hover:text-indigo-600 transition-all transform hover:scale-110 active:scale-90">
                            <input type="file" @change="handleFileChange" class="hidden" accept="image/*">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                              <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </label>

                        <!-- Voice Input -->
                        <button 
                            @mousedown="startRecording"
                            @mouseup="stopRecording"
                            @mouseleave="stopRecording"
                            @touchstart.prevent="startRecording"
                            @touchend.prevent="stopRecording"
                            type="button"
                            class="p-3 rounded-2xl transition-all transform hover:scale-110 active:scale-95"
                            :class="isRecording 
                                ? 'bg-rose-500 text-white shadow-lg shadow-rose-500/50 animate-pulse' 
                                : 'text-gray-400 hover:text-indigo-600'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z" />
                            </svg>
                        </button>

                        <div class="relative flex-1">
                            <input 
                                ref="inputRef"
                                v-model="form.message" 
                                @input="handleInput"
                                @keydown="handleMentionKeydown"
                                type="text" 
                                class="w-full bg-gray-100/50 dark:bg-black/30 border-none rounded-2xl px-6 py-4 focus:ring-2 focus:ring-indigo-500 dark:text-white placeholder-gray-400 sm:text-lg transition-all" 
                                :placeholder="isRecording ? 'Listening...' : 'Type @ to mention agents...'"
                                :disabled="form.processing"
                                autofocus
                            >
                            <!-- @-Mention Popup -->
                            <div 
                                v-if="showMentionPopup && mentionableAgents.length > 0"
                                class="absolute bottom-full left-0 mb-2 w-64 bg-gray-800 rounded-lg shadow-xl border border-gray-700 overflow-hidden z-50"
                            >
                                <div class="p-2 text-xs text-gray-400 border-b border-gray-700">Select agent</div>
                                <button
                                    v-for="(agent, i) in mentionableAgents"
                                    :key="agent.id"
                                    @click="selectMention(agent)"
                                    :class="[
                                        'w-full px-4 py-2 text-left flex items-center gap-2 transition-colors',
                                        i === mentionIndex ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700'
                                    ]"
                                >
                                    <span class="text-lg">🤖</span>
                                    <span class="font-medium">{{ agent.name }}</span>
                                    <span class="text-xs opacity-60">{{ agent.model }}</span>
                                </button>
                            </div>
                        </div>
                        <button 
                            type="submit" 
                            class="group relative flex items-center justify-center w-12 h-12 sm:w-16 sm:h-14 bg-indigo-600 text-white rounded-2xl hover:bg-indigo-500 disabled:opacity-50 transition-all duration-300 shadow-xl shadow-indigo-600/30 overflow-hidden"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing" class="animate-spin w-5 h-5 border-2 border-white/30 border-t-white rounded-full"></span>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6 group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0 1 21.485 12 59.77 59.77 0 0 1 3.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.3s ease-out forwards;
}

.custom-scrollbar-v::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar-v::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar-v::-webkit-scrollbar-thumb {
    background: rgba(99, 102, 241, 0.1);
    border-radius: 10px;
}
.custom-scrollbar-v:hover::-webkit-scrollbar-thumb {
    background: rgba(99, 102, 241, 0.3);
}

.custom-scrollbar::-webkit-scrollbar {
    height: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(16, 185, 129, 0.2);
    border-radius: 10px;
}
</style>
