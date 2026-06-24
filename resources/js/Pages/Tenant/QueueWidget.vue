<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    services: Array,
    initialQueue: Object,
    tenant: Object,
});

// Real-time queue state
const queue = ref(props.initialQueue);
const isSubmitting = ref(false);
const showJoinModal = ref(false);
const showMyTicket = ref(null);

const form = useForm({
    customer_name: '',
    customer_phone: '',
    service_id: '',
});

// Format name for privacy (e.g., "John Doe" -> "John D.")
const formatName = (name) => {
    if (!name) return '';
    const parts = name.split(' ');
    if (parts.length > 1) {
        return `${parts[0]} ${parts[1].charAt(0)}.`;
    }
    return name;
};

// Check-in walk-in
const submitJoin = () => {
    isSubmitting.value = true;
    axios.post(route('tenant.queue.join'), {
        customer_name: form.customer_name,
        customer_phone: form.customer_phone,
        service_id: form.service_id,
    })
    .then(response => {
        if (response.data.success) {
            showMyTicket.value = response.data.ticket;
            // Update queue locally immediately
            fetchQueueStatus();
            form.reset();
            showJoinModal.value = false;
        }
    })
    .catch(err => {
        console.error(err);
        alert(err.response?.data?.message || 'Failed to join the queue.');
    })
    .finally(() => {
        isSubmitting.value = false;
    });
};

// Fetch current queue status manually if needed
const fetchQueueStatus = () => {
    axios.get(route('tenant.queue.status'))
        .then(response => {
            queue.value = response.data;
        });
};

// Echo WebSocket registration
onMounted(() => {
    if (window.Echo) {
        window.Echo.channel(`tenant.${props.tenant.id}.queue`)
            .listen('.QueueUpdated', (e) => {
                console.log('Real-time queue update received:', e);
                queue.value = e.items;
            });
    }
    
    // Fallback polling every 10 seconds in case WebSockets fail
    const interval = setInterval(fetchQueueStatus, 10000);
    onUnmounted(() => {
        clearInterval(interval);
        if (window.Echo) {
            window.Echo.leave(`tenant.${props.tenant.id}.queue`);
        }
    });
});
</script>

<template>
    <Head :title="tenant.id.toUpperCase() + ' - Real-Time Queue'" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans selection:bg-indigo-500 selection:text-white">
        <!-- Background decorative glows -->
        <div class="fixed top-0 left-1/4 w-[500px] h-[500px] bg-indigo-500/10 rounded-full blur-[150px] pointer-events-none"></div>
        <div class="fixed bottom-0 right-1/4 w-[500px] h-[500px] bg-blue-500/10 rounded-full blur-[150px] pointer-events-none"></div>

        <!-- Header -->
        <header class="border-b border-slate-900 bg-slate-950/70 backdrop-blur-md sticky top-0 z-20">
            <div class="max-w-6xl mx-auto px-6 py-5 flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-blue-500 flex items-center justify-center font-bold text-white shadow-lg shadow-indigo-500/20">
                        {{ tenant.id.substring(0, 2).toUpperCase() }}
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight bg-gradient-to-r from-white to-slate-400 bg-clip-text text-transparent capitalize">{{ tenant.id }}</h1>
                        <span class="text-[10px] uppercase tracking-wider text-slate-500 font-semibold">Live Waitlist</span>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Live Connection
                    </span>
                    <a :href="route('login')" class="text-sm font-semibold text-slate-400 hover:text-white transition">Admin Portal</a>
                </div>
            </div>
        </header>

        <!-- Main Workspace -->
        <main class="max-w-6xl w-full mx-auto px-6 py-10 flex-grow grid grid-cols-1 lg:grid-cols-3 gap-8 z-10">
            
            <!-- Sidebar: Join Queue & General Stats -->
            <div class="space-y-6 lg:col-span-1">
                <!-- Stats card -->
                <div class="bg-slate-900/60 border border-slate-800 rounded-3xl p-6 shadow-xl backdrop-blur-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-indigo-500/10 to-transparent rounded-full"></div>
                    
                    <h3 class="text-xs uppercase tracking-wider text-slate-500 font-bold mb-4">Estimated Wait Time</h3>
                    <div class="flex items-baseline space-x-2">
                        <span class="text-6xl font-extrabold tracking-tight bg-gradient-to-br from-white to-slate-300 bg-clip-text text-transparent">
                            {{ queue.stats.avg_wait_minutes }}
                        </span>
                        <span class="text-slate-400 font-semibold">mins</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">Calculated in real-time based on active queue size.</p>

                    <div class="mt-6 pt-6 border-t border-slate-800/80 grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-[10px] uppercase text-slate-500 font-bold">Waiting</span>
                            <span class="text-2xl font-bold">{{ queue.stats.total_waiting }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] uppercase text-slate-500 font-bold">In Service</span>
                            <span class="text-2xl font-bold">{{ queue.stats.total_in_service }}</span>
                        </div>
                    </div>
                </div>

                <!-- Call to Action button -->
                <button @click="showJoinModal = true" class="w-full bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 text-white font-bold py-4 rounded-2xl transition duration-300 shadow-lg shadow-indigo-600/20 hover:shadow-indigo-600/40 flex items-center justify-center space-x-2 text-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span>Check-In Now</span>
                </button>

                <!-- Personal Ticket Card if joined -->
                <div v-if="showMyTicket" class="bg-indigo-950/40 border border-indigo-800/60 rounded-3xl p-6 shadow-xl relative overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-24 h-24 bg-indigo-500/20 rounded-full blur-xl"></div>
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h4 class="text-indigo-400 text-sm font-bold">Your Queue Ticket</h4>
                            <p class="text-xs text-slate-400 mt-0.5">Please take a screenshot of this card</p>
                        </div>
                        <span class="text-xs font-semibold bg-indigo-500/20 text-indigo-300 px-2 py-0.5 rounded-full">Active</span>
                    </div>

                    <div class="text-center py-4 bg-slate-950/60 border border-indigo-900/40 rounded-2xl mb-4">
                        <span class="block text-4xl font-extrabold text-white tracking-widest">{{ showMyTicket.ticket_number }}</span>
                        <span class="text-[10px] uppercase tracking-wider text-slate-500 font-bold mt-1 block">Ticket Number</span>
                    </div>

                    <div class="space-y-2 text-sm text-slate-300">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Name:</span>
                            <span class="font-medium text-slate-200">{{ showMyTicket.customer_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Service:</span>
                            <span class="font-medium text-slate-200">{{ showMyTicket.service_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Est. Wait Time:</span>
                            <span class="font-medium text-indigo-400 font-semibold">{{ showMyTicket.estimated_wait_minutes }} mins</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Queue Dashboard Boards (Waiting & In Service lists) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- In Service Section -->
                <div class="bg-slate-900/40 border border-slate-900 rounded-3xl p-6 shadow-xl">
                    <div class="flex items-center space-x-2 mb-6">
                        <span class="flex h-2 w-2 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                        </span>
                        <h2 class="text-lg font-bold text-slate-200">Currently in Service</h2>
                    </div>

                    <div v-if="queue.in_service.length === 0" class="text-center py-10 bg-slate-950/40 border border-dashed border-slate-900 rounded-2xl">
                        <span class="text-sm text-slate-500">No customers are currently in service.</span>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="item in queue.in_service" :key="item.id" class="bg-gradient-to-r from-indigo-950/30 to-slate-900/60 border border-indigo-900/40 rounded-2xl p-4 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-indigo-400 block tracking-wider">Ticket</span>
                                <span class="text-2xl font-extrabold text-white">{{ item.ticket_number }}</span>
                                <p class="text-xs text-slate-400 mt-1 capitalize">{{ item.service_name }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-semibold text-slate-200 block">{{ formatName(item.customer_name) }}</span>
                                <span class="text-[10px] text-slate-500 block mt-1">Called {{ new Date(item.called_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Waitlist Section -->
                <div class="bg-slate-900/40 border border-slate-900 rounded-3xl p-6 shadow-xl">
                    <h2 class="text-lg font-bold text-slate-200 mb-6">Queue Waiting List</h2>

                    <div v-if="queue.waiting.length === 0" class="text-center py-12 bg-slate-950/40 border border-dashed border-slate-900 rounded-2xl">
                        <span class="text-sm text-slate-500">Queue is empty. Join above!</span>
                    </div>

                    <div v-else class="overflow-hidden border border-slate-900 rounded-2xl">
                        <table class="min-w-full divide-y divide-slate-900 text-left">
                            <thead class="bg-slate-900/60">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-xs font-bold uppercase tracking-wider text-slate-400">Ticket</th>
                                    <th scope="col" class="px-3 py-3.5 text-xs font-bold uppercase tracking-wider text-slate-400">Customer</th>
                                    <th scope="col" class="px-3 py-3.5 text-xs font-bold uppercase tracking-wider text-slate-400">Service</th>
                                    <th scope="col" class="px-3 py-3.5 text-xs font-bold uppercase tracking-wider text-slate-400 text-right">Est. Wait</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-900 bg-slate-950/20">
                                <tr v-for="item in queue.waiting" :key="item.id" class="hover:bg-slate-900/20 transition">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 font-mono font-bold text-indigo-400 text-lg">{{ item.ticket_number }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-slate-200">{{ formatName(item.customer_name) }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-400 capitalize">{{ item.service_name }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-slate-200 text-right">{{ item.estimated_wait_minutes }} min</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <!-- Join Queue Modal (Slide Up / Modal Glassmorphism) -->
        <div v-if="showJoinModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl relative">
                <button @click="showJoinModal = false" class="absolute top-4 right-4 text-slate-500 hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <h3 class="text-xl font-bold text-white mb-2">Check-In</h3>
                <p class="text-xs text-slate-400 mb-6">Enter your details to join the live waitlist.</p>

                <form @submit.prevent="submitJoin" class="space-y-4">
                    <div>
                        <label for="customer_name" class="block text-xs uppercase tracking-wider text-slate-400 mb-2 font-semibold">Your Name</label>
                        <input type="text" id="customer_name" v-model="form.customer_name" placeholder="John Doe" required
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white placeholder-slate-650 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    </div>

                    <div>
                        <label for="customer_phone" class="block text-xs uppercase tracking-wider text-slate-400 mb-2 font-semibold">Phone Number (Optional)</label>
                        <input type="text" id="customer_phone" v-model="form.customer_phone" placeholder="+1 (555) 000-0000"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white placeholder-slate-650 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        <p class="text-[10px] text-slate-500 mt-1">Provide this to receive a check-in reminder.</p>
                    </div>

                    <div>
                        <label for="service_id" class="block text-xs uppercase tracking-wider text-slate-400 mb-2 font-semibold">Requested Service</label>
                        <select id="service_id" v-model="form.service_id"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                            <option value="">General Walk-in (First Available)</option>
                            <option v-for="service in services" :key="service.id" :value="service.id">
                                {{ service.name }} ({{ service.duration_minutes }}m) - ${{ service.price }}
                            </option>
                        </select>
                    </div>

                    <button type="submit" :disabled="isSubmitting"
                        class="w-full bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 text-white font-bold py-3.5 rounded-xl transition duration-300 shadow-lg shadow-indigo-600/20 flex items-center justify-center space-x-2 mt-6">
                        <span v-if="isSubmitting">Joining waitlist...</span>
                        <span v-else>Confirm Check-In</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
