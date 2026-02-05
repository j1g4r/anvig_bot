<script setup>
import { ref, watch, nextTick } from 'vue';

const props = defineProps({
    messages: {
        type: Array,
        required: true,
        default: () => []
    },
    expanded: {
        type: Boolean,
        default: true
    }
});

defineEmits(['toggle']);
</script>

<template>
    <div class="pointer-events-auto flex flex-col h-full bg-black/80 backdrop-blur-xl border border-blue-500/20 rounded-lg shadow-[0_0_30px_rgba(0,0,0,0.5)] overflow-hidden transition-all duration-300 transform"
         :class="expanded ? 'w-[300px]' : 'w-[40px] opacity-80 hover:opacity-100'">
        
        <!-- Header / Toggle -->
        <div class="flex items-center justify-between p-3 border-b border-white/10 bg-gradient-to-r from-gray-900 to-black cursor-pointer hover:bg-white/5"
             @click="$emit('toggle')">
            <h3 v-if="expanded" class="text-blue-400 font-black uppercase text-xs tracking-widest truncate">Comms Uplink</h3>
            <div class="text-gray-400">
                <svg v-if="expanded" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
            <!-- Icon when collapsed -->
            <div v-if="!expanded" class="absolute top-12 left-1/2 -translate-x-1/2 flex flex-col items-center gap-4">
                 <div class="writing-vertical text-[0.6rem] font-bold text-blue-500 uppercase tracking-widest whitespace-nowrap">COMMS SITE</div>
            </div>
        </div>

        <!-- Content -->
        <div v-if="expanded" class="flex-1 overflow-y-auto custom-scrollbar p-2 flex flex-col gap-2 relative">
            <TransitionGroup name="list">
                <div v-for="msg in messages" :key="msg.id" 
                     class="group relative bg-gray-900/40 border-l-2 border-gray-700 p-2 text-[0.65rem] font-mono hover:bg-black/60 transition shadow-lg hover:shadow-blue-900/10 hover:border-blue-500">
                    
                    <div class="flex justify-between text-blue-300 mb-0.5 opacity-70">
                        <span class="font-bold uppercase text-blue-200">{{ msg.agent }}</span>
                        <span class="text-[0.6rem]">{{ msg.timestamp }}</span>
                    </div>
                    
                    <div class="text-white/80 break-words leading-relaxed">
                        <span v-if="msg.tool" class="text-purple-400 opacity-80 font-bold">[{{ msg.tool }}]</span> 
                        <span :class="{'text-gray-400': !msg.message}">{{ msg.message || 'Processing...' }}</span>
                    </div>
                </div>
            </TransitionGroup>
            
            <div v-if="messages.length === 0" class="flex-1 flex items-center justify-center text-gray-600 text-xs italic">
                No signal detected...
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.1);
    border-radius: 2px;
}
.writing-vertical {
    writing-mode: vertical-rl;
    text-orientation: mixed;
}
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}
</style>
