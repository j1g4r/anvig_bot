<script setup>
import { ref, computed } from 'vue';
import MarkdownIt from 'markdown-it';
import hljs from 'highlight.js';
import 'highlight.js/styles/github-dark.css';

const props = defineProps({
    msg: Object,
    showThoughts: Boolean,
});

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

const isCollapsed = ref(true); // Default to collapsed

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const parseReasoning = (content) => {
    if (!content) return { monologue: null, cleanContent: '' };
    
    // Extract sections
    const sections = [
        { key: 'thought', regex: /<THOUGHT>([\s\S]*?)<\/THOUGHT>/i },
        { key: 'plan', regex: /<PLAN>([\s\S]*?)<\/PLAN>/i },
        { key: 'critique', regex: /<CRITIQUE>([\s\S]*?)<\/CRITIQUE>/i },
        { key: 'action', regex: /<ACTION>([\s\S]*?)<\/ACTION>/i },
    ];

    const monologue = {};
    let hasMonologue = false;

    sections.forEach(({ key, regex }) => {
        const match = content.match(regex);
        if (match) {
            monologue[key] = md.render(match[1].trim());
            hasMonologue = true;
        }
    });

    // Remove tags for clean content
    let cleanContent = content
        .replace(/<(THOUGHT|PLAN|CRITIQUE|ACTION)>[\s\S]*?<\/\1>/gi, '')
        .trim();
        
    return {
        monologue: hasMonologue ? monologue : null,
        cleanContent: md.render(cleanContent)
    };
};

const parsedContent = computed(() => parseReasoning(props.msg.content));

const foundTags = computed(() => {
    const tags = [];
    if (parsedContent.value.monologue?.thought) tags.push({ label: 'THOUGHT', icon: '🧠', color: 'text-gray-500 bg-gray-100 dark:bg-gray-800' });
    if (parsedContent.value.monologue?.plan) tags.push({ label: 'PLAN', icon: '📅', color: 'text-blue-500 bg-blue-100 dark:bg-blue-900/30' });
    if (parsedContent.value.monologue?.critique) tags.push({ label: 'CRITIQUE', icon: '⚖️', color: 'text-rose-500 bg-rose-100 dark:bg-rose-900/30' });
    if (parsedContent.value.monologue?.action) tags.push({ label: 'ACTION', icon: '⚡', color: 'text-amber-500 bg-amber-100 dark:bg-amber-900/30' });
    return tags;
});

// Feedback for Continuous Learning
const feedbackState = ref(null); // null, 'positive', 'negative'
const feedbackSubmitting = ref(false);

const emit = defineEmits(['feedback']);

const submitFeedback = async (score) => {
    if (!props.msg.id || feedbackSubmitting.value) return;
    
    feedbackSubmitting.value = true;
    try {
        await fetch('/api/learning/feedback', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                message_id: props.msg.id,
                score: score,
            }),
        });
        feedbackState.value = score > 0 ? 'positive' : 'negative';
        emit('feedback', { messageId: props.msg.id, score });
    } catch (error) {
        console.error('Feedback submission failed:', error);
    } finally {
        feedbackSubmitting.value = false;
    }
};
</script>

<template>
    <div class="flex flex-col space-y-3 w-full">
        <!-- User Message -->
        <div v-if="msg.role === 'user'" class="flex justify-end">
            <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 text-white rounded-[1.5rem] rounded-tr-sm px-6 py-4 max-w-[90%] sm:max-w-[75%] shadow-xl shadow-indigo-600/10 border border-white/10">
                {{ msg.content }}
            </div>
        </div>
        
        <!-- Assistant Message -->
        <div v-if="msg.role === 'assistant'" class="flex justify-start flex-col items-start gap-4">
            
            <!-- Monologue / Reasoning Block (Collapsible) -->
            <div v-if="showThoughts && parsedContent.monologue" class="w-full max-w-[90%] sm:max-w-[75%] ml-4">
                
                <!-- Toggle Header -->
                <button 
                    @click="toggleCollapse"
                    class="flex items-center gap-2 px-4 py-2 bg-indigo-50/50 dark:bg-indigo-950/20 backdrop-blur-md rounded-full border border-indigo-500/10 hover:bg-indigo-100/50 dark:hover:bg-indigo-900/30 transition-colors group cursor-pointer"
                >
                    <div class="bg-indigo-500/10 dark:bg-indigo-400/10 p-1.5 rounded-full group-hover:bg-indigo-500/20 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" 
                            class="w-3.5 h-3.5 text-indigo-500 transform transition-transform duration-300"
                            :class="{ 'rotate-180': !isCollapsed }">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-indigo-500/80 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">
                        {{ isCollapsed ? 'Show Neural Flow' : 'Hide Neural Flow' }}
                    </span>
                    
                    <div v-if="isCollapsed" class="flex gap-1 ml-2">
                        <span v-for="tag in foundTags" :key="tag.label" :class="tag.color" class="text-[9px] font-black px-1.5 py-0.5 rounded uppercase tracking-wider flex items-center gap-1">
                            <span>{{ tag.icon }}</span>
                            <span class="hidden xs:inline">{{ tag.label }}</span>
                        </span>
                    </div>
                </button>

                <!-- Expanded Content -->
                <transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-2"
                >
                    <div v-show="!isCollapsed" class="mt-3 space-y-3">
                        
                        <!-- THOUGHT Block -->
                        <div v-if="parsedContent.monologue.thought" class="bg-gray-50 dark:bg-gray-900/50 rounded-2xl p-5 border-l-4 border-gray-400 dark:border-gray-600 shadow-sm relative overflow-hidden">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] bg-gray-200 dark:bg-gray-800 px-2 py-0.5 rounded">🧠 Thought</span>
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-300 font-medium leading-relaxed prose prose-sm dark:prose-invert max-w-none" v-html="parsedContent.monologue.thought"></div>
                        </div>

                        <!-- PLAN Block -->
                        <div v-if="parsedContent.monologue.plan" class="bg-blue-50 dark:bg-blue-950/20 rounded-2xl p-5 border-l-4 border-blue-500 shadow-sm relative overflow-hidden">
                             <div class="flex items-center gap-2 mb-2">
                                <span class="text-[10px] font-black text-blue-500 uppercase tracking-[0.2em] bg-blue-100 dark:bg-blue-900/50 px-2 py-0.5 rounded">📅 Plan</span>
                            </div>
                            <div class="text-xs text-gray-700 dark:text-gray-300 font-medium leading-relaxed prose prose-blue dark:prose-invert max-w-none" v-html="parsedContent.monologue.plan"></div>
                        </div>

                        <!-- CRITIQUE Block -->
                        <div v-if="parsedContent.monologue.critique" class="bg-rose-50 dark:bg-rose-950/20 rounded-2xl p-5 border-l-4 border-rose-500 shadow-sm relative overflow-hidden">
                             <div class="flex items-center gap-2 mb-2">
                                <span class="text-[10px] font-black text-rose-500 uppercase tracking-[0.2em] bg-rose-100 dark:bg-rose-900/50 px-2 py-0.5 rounded">⚖️ Critique</span>
                            </div>
                            <div class="text-xs text-gray-700 dark:text-gray-300 font-medium leading-relaxed prose prose-rose dark:prose-invert max-w-none" v-html="parsedContent.monologue.critique"></div>
                        </div>
                        
                         <!-- ACTION Block -->
                        <div v-if="parsedContent.monologue.action" class="bg-amber-50 dark:bg-amber-950/20 rounded-2xl p-5 border-l-4 border-amber-500 shadow-sm relative overflow-hidden">
                             <div class="flex items-center gap-2 mb-2">
                                <span class="text-[10px] font-black text-amber-600 dark:text-amber-500 uppercase tracking-[0.2em] bg-amber-100 dark:bg-amber-900/50 px-2 py-0.5 rounded">⚡ Action</span>
                            </div>
                            <div class="text-xs text-gray-700 dark:text-gray-300 font-medium leading-relaxed prose prose-amber dark:prose-invert max-w-none" v-html="parsedContent.monologue.action"></div>
                        </div>

                    </div>
                </transition>
            </div>

            <!-- Clean Content Bubble -->
            <div v-if="parsedContent.cleanContent" class="bg-white/80 dark:bg-gray-900/90 backdrop-blur-md text-gray-800 dark:text-gray-100 rounded-[1.5rem] rounded-tl-sm px-6 py-4 max-w-[95%] sm:max-w-[85%] shadow-xl border border-white/40 dark:border-gray-800/60 group relative overflow-hidden">
                <!-- Subtle highlight -->
                <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500/50"></div>

                <div class="prose prose-sm sm:prose-base dark:prose-invert max-w-none" v-html="parsedContent.cleanContent"></div>
                
                <!-- Tool calls -->
                <div v-if="showThoughts && msg.tool_calls" class="mt-5 overflow-hidden rounded-2xl border border-orange-500/10 dark:border-orange-500/20">
                    <div class="bg-orange-50/50 dark:bg-orange-950/20 px-4 py-3 flex items-center gap-3">
                        <div class="flex space-x-1">
                            <div class="w-1.5 h-1.5 bg-orange-500 rounded-full animate-pulse"></div>
                            <div class="w-1.5 h-1.5 bg-orange-500 rounded-full animate-pulse [animation-delay:200ms]"></div>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-[0.2em] text-orange-600 dark:text-orange-400">System Execution</span> 
                    </div>
                    <div class="bg-gray-50/30 dark:bg-black/20 p-4 flex flex-col gap-3">
                        <div v-for="tc in msg.tool_calls" :key="tc.id" class="flex items-center gap-3 text-[11px] font-mono text-gray-600 dark:text-gray-400">
                            <div class="px-3 py-1 bg-white/80 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-2">
                                <span class="text-indigo-500">λ</span>
                                <span>{{ tc.function.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feedback Buttons for Continuous Learning -->
                <div class="mt-4 pt-4 border-t border-gray-200/30 dark:border-gray-700/30 flex items-center justify-between opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    <span class="text-[10px] text-gray-400 dark:text-gray-500">Was this helpful?</span>
                    <div class="flex items-center gap-2">
                        <button 
                            @click="submitFeedback(1)" 
                            :disabled="feedbackSubmitting || feedbackState !== null"
                            :class="[
                                'p-1.5 rounded-lg transition-all duration-200',
                                feedbackState === 'positive' 
                                    ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600' 
                                    : 'hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-400 hover:text-emerald-500',
                                feedbackState !== null && feedbackState !== 'positive' ? 'opacity-30' : ''
                            ]"
                            title="Helpful"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                <path d="M1 8.25a1.25 1.25 0 1 1 2.5 0v7.5a1.25 1.25 0 1 1-2.5 0v-7.5ZM11 3V1.7c0-.268.14-.526.395-.607A2 2 0 0 1 14 3c0 .995-.182 1.948-.514 2.826-.204.54.166 1.174.744 1.174h2.52c1.243 0 2.261 1.01 2.146 2.247a23.864 23.864 0 0 1-1.341 5.974C17.153 16.323 16.072 17 14.9 17h-3.192a3 3 0 0 1-1.341-.317l-2.734-1.366A3 3 0 0 0 6.292 15H5V8h.963c.685 0 1.258-.483 1.612-1.068a4.011 4.011 0 0 1 2.166-1.73c.432-.143.853-.386 1.011-.814.16-.432.248-.9.248-1.388Z" />
                            </svg>
                        </button>
                        <button 
                            @click="submitFeedback(-1)" 
                            :disabled="feedbackSubmitting || feedbackState !== null"
                            :class="[
                                'p-1.5 rounded-lg transition-all duration-200',
                                feedbackState === 'negative' 
                                    ? 'bg-rose-100 dark:bg-rose-900/40 text-rose-600' 
                                    : 'hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-400 hover:text-rose-500',
                                feedbackState !== null && feedbackState !== 'negative' ? 'opacity-30' : ''
                            ]"
                            title="Not helpful"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                <path d="M18.905 12.75a1.25 1.25 0 1 1-2.5 0v-7.5a1.25 1.25 0 0 1 2.5 0v7.5ZM8.905 17v1.3c0 .268-.14.526-.395.607A2 2 0 0 1 5.905 17c0-.995.182-1.948.514-2.826.204-.54-.166-1.174-.744-1.174h-2.52c-1.242 0-2.26-1.01-2.146-2.247.193-2.08.652-4.082 1.341-5.974C2.752 3.678 3.833 3 5.005 3h3.192a3 3 0 0 1 1.342.317l2.733 1.366A3 3 0 0 0 13.613 5h1.292v7h-.963c-.684 0-1.258.482-1.612 1.068a4.012 4.012 0 0 1-2.165 1.73c-.433.143-.854.386-1.012.814-.16.432-.248.9-.248 1.388Z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tool Output Message -->
        <div v-if="showThoughts && msg.role === 'tool'" class="flex justify-start pl-4 sm:pl-12 w-full">
            <div class="bg-[#0c0c0e] text-emerald-400 font-mono text-[10px] sm:text-[11px] rounded-2xl px-5 py-5 max-w-full shadow-2xl border border-white/5 group w-full overflow-hidden">
                <div class="opacity-20 mb-4 border-b border-white/10 pb-2 flex justify-between uppercase tracking-[0.3em] font-black text-[8px]">
                    <span>I/O PIPE</span>
                    <span>ID_{{ msg.tool_call_id.substring(0, 6) }}</span>
                </div>
                <pre class="overflow-x-auto custom-scrollbar whitespace-pre-wrap leading-tight">{{ msg.content }}</pre>
            </div>
        </div>

        <!-- System Handover Message -->
        <div v-if="showThoughts && msg.role === 'system' && msg.content.includes('HANDOVER')" class="flex justify-center my-4">
            <div class="bg-indigo-500/10 dark:bg-indigo-400/5 backdrop-blur-sm border border-indigo-500/20 rounded-full px-6 py-2 flex items-center space-x-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 text-indigo-500">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
                <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">{{ msg.content }}</span>
            </div>
        </div>

        <!-- Autonomous Debugger Message -->
        <div v-if="showThoughts && msg.role === 'system' && msg.content.includes('AUTONOMOUS DEBUGGER')" class="flex justify-center my-4">
            <div class="bg-rose-500/10 dark:bg-rose-400/5 backdrop-blur-sm border border-rose-500/30 rounded-full px-6 py-2 flex items-center space-x-3 shadow-[0_0_20px_rgba(244,63,94,0.1)]">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 text-rose-500 animate-spin">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0 1 15 0m-15 0a7.5 7.5 0 1 1 15 0m-15 0H3m16.5 0h1.5m-1.5 0a3 3 0 0 1-3 3m3-3a3 3 0 0 0-3-3m-9 3a3 3 0 0 1 3 3m-3-3a3 3 0 0 0 3-3" />
                </svg>
                <span class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest animate-pulse">🔧 Self-Healing In Progress</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
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
