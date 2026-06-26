<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    services: Array,
    initialQueue: Object,
    tenant: Object,
});

const queue = ref(props.initialQueue);
const isMuted = ref(true);
const lastAnnouncedId = ref(null);
const flashTicket = ref(null);

// Audio Context for Chime Generation
let audioCtx = null;

const initAudio = () => {
    isMuted.value = !isMuted.value;
    
    // Resume AudioContext if browser suspended it
    if (!isMuted.value) {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (AudioContextClass && !audioCtx) {
            audioCtx = new AudioContextClass();
        }
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        
        // Play a test chime to confirm audio is active
        playChime();
    }
};

const playChime = () => {
    try {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) return;
        if (!audioCtx) {
            audioCtx = new AudioContextClass();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }

        // Note 1: C5 (523.25 Hz)
        const osc1 = audioCtx.createOscillator();
        const gain1 = audioCtx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(523.25, audioCtx.currentTime);
        gain1.gain.setValueAtTime(0.08, audioCtx.currentTime);
        gain1.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.4);
        
        osc1.connect(gain1);
        gain1.connect(audioCtx.destination);
        osc1.start(audioCtx.currentTime);
        osc1.stop(audioCtx.currentTime + 0.4);
        
        // Note 2: E5 (659.25 Hz) - played with 200ms delay
        const osc2 = audioCtx.createOscillator();
        const gain2 = audioCtx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(659.25, audioCtx.currentTime + 0.2);
        gain2.gain.setValueAtTime(0.08, audioCtx.currentTime + 0.2);
        gain2.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.6);
        
        osc2.connect(gain2);
        gain2.connect(audioCtx.destination);
        osc2.start(audioCtx.currentTime + 0.2);
        osc2.stop(audioCtx.currentTime + 0.6);
    } catch (err) {
        console.error("Chime play failed:", err);
    }
};

const speakTicket = (ticketNumber, customerName, counterName) => {
    if (!window.speechSynthesis) return;
    
    // Cancel active speech to prevent queuing lag
    window.speechSynthesis.cancel();
    
    // Spell out ticket number (e.g. Q - 0 - 0 - 1) for clarity
    const spelledTicket = ticketNumber.split('').join(' ');
    const counter = counterName || 'the main desk';
    const text = `Ticket number, ${spelledTicket}, ${customerName}, please proceed to ${counter}.`;
    
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.rate = 0.85; // Slower for clear audio delivery in lobbies
    utterance.pitch = 1.0;
    
    // Find premium system voice (English) if available
    const voices = window.speechSynthesis.getVoices();
    const premiumVoice = voices.find(v => v.lang.startsWith('en') && v.name.includes('Google'));
    const defaultVoice = voices.find(v => v.lang.startsWith('en'));
    utterance.voice = premiumVoice || defaultVoice || null;
    
    window.speechSynthesis.speak(utterance);
};

const fetchQueueStatus = () => {
    axios.get(route('tenant.queue.status'))
        .then(response => {
            queue.value = response.data;
        });
};

// Watcher to detect and announce newly called tickets
watch(() => queue.value.in_service, (newInService) => {
    if (!newInService || newInService.length === 0) return;
    
    // The last item in in_service represents the most recently called ticket
    const latestTicket = newInService[newInService.length - 1];
    
    if (latestTicket.id !== lastAnnouncedId.value) {
        const isFirstLoad = lastAnnouncedId.value === null;
        lastAnnouncedId.value = latestTicket.id;
        
        // Skip announcement on initial page load to avoid noisy loads
        if (isFirstLoad) return;
        
        // Visual flash trigger
        flashTicket.value = latestTicket.ticket_number;
        setTimeout(() => {
            flashTicket.value = null;
        }, 6000);
        
        if (!isMuted.value) {
            playChime();
            setTimeout(() => {
                speakTicket(latestTicket.ticket_number, latestTicket.customer_name, latestTicket.counter_name);
            }, 700);
        }
    }
}, { deep: true });

onMounted(() => {
    // Populate voices for speech synthesis
    if (window.speechSynthesis) {
        window.speechSynthesis.getVoices();
        if (window.speechSynthesis.onvoiceschanged !== undefined) {
            window.speechSynthesis.onvoiceschanged = window.speechSynthesis.getVoices;
        }
    }

    // Set initial lastAnnouncedId based on load state
    if (queue.value.in_service && queue.value.in_service.length > 0) {
        const latest = queue.value.in_service[queue.value.in_service.length - 1];
        lastAnnouncedId.value = latest.id;
    }

    // Subscribe to WebSockets
    if (window.Echo) {
        window.Echo.channel(`tenant.${props.tenant.id}.queue`)
            .listen('.QueueUpdated', (e) => {
                console.log('Lobby display received update:', e);
                queue.value = e.items;
            });
    }

    // Polling fallback
    const interval = setInterval(fetchQueueStatus, 12000);
    onUnmounted(() => {
        clearInterval(interval);
        if (window.Echo) {
            window.Echo.leave(`tenant.${props.tenant.id}.queue`);
        }
    });
});
</script>

<template>
    <Head title="Lobby TV Queue Display" />

    <div class="min-h-screen bg-slate-950 text-white font-sans overflow-hidden flex flex-col relative select-none">
        
        <!-- Background Ambient Glows -->
        <div class="absolute top-[-20%] left-[-10%] w-[50vw] h-[50vw] rounded-full bg-indigo-900/10 blur-[150px] pointer-events-none"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[50vw] h-[50vw] rounded-full bg-emerald-900/10 blur-[150px] pointer-events-none"></div>

        <!-- Autoplay Audio Banner -->
        <div v-if="isMuted" class="bg-indigo-600/90 backdrop-blur text-white text-center py-3.5 px-4 font-semibold text-sm flex items-center justify-center space-x-3 cursor-pointer z-50 transition border-b border-indigo-500/30 animate-pulse" @click="initAudio">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
            <span>Audio is muted. Click anywhere on this banner to enable voice announcements & lobby chimes.</span>
        </div>
        
        <div v-else class="bg-slate-900/40 border-b border-slate-800/40 backdrop-blur px-6 py-3 flex justify-between items-center text-xs text-slate-500 z-50">
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                <span class="font-semibold text-slate-400">Live Connection Active</span>
            </div>
            <button @click="isMuted = true" class="flex items-center space-x-1 hover:text-indigo-400 transition font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                <span>Mute System</span>
            </button>
        </div>

        <!-- Main Display Layout -->
        <div class="flex-1 grid grid-cols-1 lg:grid-cols-5 p-6 lg:p-10 gap-6 lg:gap-10">
            
            <!-- Left Side: Now Serving Area (Covers 3/5 cols) -->
            <div class="lg:col-span-3 flex flex-col justify-between space-y-6">
                <!-- TV Header -->
                <div class="flex items-center justify-between bg-slate-900/30 border border-slate-800/40 rounded-2xl px-6 py-4 backdrop-blur-md">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-violet-500 flex items-center justify-center font-black text-xl text-white shadow-lg shadow-indigo-500/20">Q</div>
                        <div>
                            <h1 class="text-lg font-bold tracking-tight bg-gradient-to-r from-white to-slate-350 bg-clip-text text-transparent capitalize">{{ tenant.id }}</h1>
                            <p class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">Lobby Queue Display</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-2xl font-mono font-bold tracking-wider text-slate-350">{{ new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                    </div>
                </div>

                <!-- Now Serving Large Widget -->
                <div class="flex-1 flex flex-col justify-center items-center rounded-3xl border border-slate-800/40 bg-slate-900/20 backdrop-blur-lg relative p-8 text-center min-h-[450px]">
                    
                    <div v-if="queue.in_service.length > 0">
                        <!-- Active serving ticket details -->
                        <div class="space-y-4">
                            <span class="text-xs uppercase tracking-widest font-extrabold text-indigo-400 bg-indigo-500/5 px-4 py-1.5 rounded-full border border-indigo-500/10">Now Serving</span>
                            
                            <!-- Massive Ticket Number with dynamic flashing animation -->
                            <div :class="[
                                'text-[120px] lg:text-[160px] font-mono font-black leading-none tracking-tighter transition duration-500 select-all filter drop-shadow-[0_0_50px_rgba(99,102,241,0.2)]',
                                flashTicket === queue.in_service[queue.in_service.length - 1].ticket_number ? 'text-indigo-400 scale-105 animate-bounce' : 'text-white'
                            ]">
                                {{ queue.in_service[queue.in_service.length - 1].ticket_number }}
                            </div>

                            <div class="space-y-1">
                                <h2 class="text-3xl lg:text-4xl font-extrabold text-slate-100 uppercase tracking-tight">
                                    {{ queue.in_service[queue.in_service.length - 1].customer_name }}
                                </h2>
                                <p class="text-sm lg:text-base font-semibold text-slate-500 uppercase tracking-wider capitalize">
                                    {{ queue.in_service[queue.in_service.length - 1].service_name }}
                                </p>
                            </div>
                        </div>

                        <!-- Direction instruction -->
                        <div class="mt-12 inline-flex items-center space-x-2 text-indigo-400 text-sm font-bold bg-indigo-950/40 border border-indigo-900/50 rounded-xl px-5 py-2.5 shadow-inner">
                            <svg class="w-4 h-4 animate-pulse" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 14h-2v-6h2v6zm0-8h-2V6h2v2z"/></svg>
                            <span>Please proceed to {{ queue.in_service[queue.in_service.length - 1].counter_name || 'the Main Desk' }}</span>
                        </div>

                        <!-- Also Serving Grid (Concurrent active counters) -->
                        <div v-if="queue.in_service.length > 1" class="mt-8 space-y-2 w-full max-w-xl text-left border-t border-slate-800/40 pt-6">
                            <h4 class="text-[10px] uppercase tracking-wider text-slate-500 font-extrabold">Also Serving</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div v-for="item in queue.in_service.slice(0, -1)" :key="item.id" class="bg-slate-900/30 border border-slate-850/50 rounded-2xl px-4 py-3 flex items-center justify-between">
                                    <div>
                                        <div class="font-mono font-bold text-lg text-indigo-350 leading-none">{{ item.ticket_number }}</div>
                                        <div class="text-[10px] text-slate-500 font-semibold mt-1 truncate max-w-[150px] uppercase">{{ item.customer_name }}</div>
                                    </div>
                                    <div class="text-[9px] font-extrabold text-indigo-400 bg-indigo-500/5 border border-indigo-500/10 rounded px-2 py-0.5 uppercase tracking-wider">
                                        {{ item.counter_name || 'Main Desk' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty Serving State -->
                    <div v-else class="text-center space-y-4">
                        <div class="w-20 h-20 rounded-full bg-slate-900/60 flex items-center justify-center text-slate-600 border border-slate-800/40 mx-auto">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-400">All caught up!</h3>
                            <p class="text-xs text-slate-600 mt-1">Lobby is currently empty or waiting for tickets to be called.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Up Next (Covers 2/5 cols) -->
            <div class="lg:col-span-2 flex flex-col justify-between space-y-6">
                <!-- Section Header -->
                <div class="bg-slate-900/30 border border-slate-800/40 rounded-2xl px-6 py-4 backdrop-blur-md">
                    <h3 class="text-sm uppercase tracking-wider text-slate-450 font-bold flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        <span>Waiting Queue (Up Next)</span>
                    </h3>
                </div>

                <!-- Scrollable Up Next Cards -->
                <div class="flex-1 rounded-3xl border border-slate-800/40 bg-slate-900/20 backdrop-blur-lg p-6 overflow-y-auto space-y-4 max-h-[calc(100vh-180px)]">
                    <div v-if="queue.waiting.length === 0" class="h-full flex flex-col justify-center items-center text-center text-slate-650 space-y-3 py-20">
                        <svg class="w-12 h-12 stroke-current" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <span class="text-sm font-semibold">No tickets waiting</span>
                    </div>

                    <div v-else class="space-y-3">
                        <div v-for="(item, index) in queue.waiting" :key="item.id" class="bg-slate-900/40 border border-slate-800/60 rounded-2xl p-4 flex items-center justify-between hover:bg-slate-900/60 hover:border-slate-700/60 transition group">
                            <div class="flex items-center space-x-4">
                                <!-- Order circle -->
                                <div class="w-8 h-8 rounded-full bg-slate-850 flex items-center justify-center text-xs font-bold text-slate-500 border border-slate-800/60 group-hover:border-indigo-500/20 group-hover:text-indigo-400 transition">
                                    {{ index + 1 }}
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-200 uppercase">{{ item.customer_name }}</h4>
                                    <p class="text-[10px] text-slate-500 uppercase tracking-wide capitalize mt-0.5">{{ item.service_name }}</p>
                                </div>
                            </div>
                            <!-- Large Ticket ID -->
                            <div class="font-mono font-black text-2xl text-indigo-400/90 tracking-tighter">
                                {{ item.ticket_number }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</template>

<style scoped>
/* Glowing ring effect */
.border-indigo-500\/10 {
    box-shadow: inset 0 0 20px rgba(99, 102, 241, 0.05);
}
</style>
