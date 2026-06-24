<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    services: Array,
    initialQueue: Object,
    tenant: Object,
});

const queue = ref(props.initialQueue);
const isProcessing = ref(false);

const fetchQueueStatus = () => {
    axios.get(route('tenant.queue.status'))
        .then(response => {
            queue.value = response.data;
        });
};

// Admin actions
const callNext = () => {
    isProcessing.value = true;
    axios.post(route('tenant.queue.call'))
        .then(response => {
            if (response.data.success) {
                fetchQueueStatus();
            } else {
                alert(response.data.message || 'No one in the waiting queue.');
            }
        })
        .finally(() => {
            isProcessing.value = false;
        });
};

const markComplete = (id) => {
    axios.post(route('tenant.queue.complete', id))
        .then(() => fetchQueueStatus());
};

const markNoShow = (id) => {
    axios.post(route('tenant.queue.noshow', id))
        .then(() => fetchQueueStatus());
};

const removeQueueItem = (id) => {
    if (confirm('Are you sure you want to remove this ticket?')) {
        axios.delete(route('tenant.queue.remove', id))
            .then(() => fetchQueueStatus());
    }
};

// Echo WebSocket registration
onMounted(() => {
    if (window.Echo) {
        window.Echo.channel(`tenant.${props.tenant.id}.queue`)
            .listen('.QueueUpdated', (e) => {
                console.log('Real-time admin queue update received:', e);
                queue.value = e.items;
            });
    }

    // Fallback polling
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
    <Head title="Staff Queue Controller" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="text-xl font-semibold leading-tight text-slate-800">
                    Queue Dashboard & Controller
                </h2>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200">
                        Tenant ID: {{ tenant.id }}
                    </span>
                    <a :href="route('tenant.home')" target="_blank" class="text-xs font-bold text-indigo-600 hover:text-indigo-900 transition flex items-center space-x-1">
                        <span>Open Customer Display</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            </div>
        </template>

        <div class="py-12 bg-slate-50 min-h-[calc(100vh-65px)]">
            <div class="mx-auto max-w-7xl px-6 lg:px-8 space-y-8">
                
                <!-- Quick Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <div>
                            <span class="block text-xs uppercase tracking-wider text-slate-400 font-bold">Total Waiting</span>
                            <span class="text-3xl font-bold text-slate-800">{{ queue.stats.total_waiting }}</span>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <span class="block text-xs uppercase tracking-wider text-slate-400 font-bold">In Service</span>
                            <span class="text-3xl font-bold text-slate-800">{{ queue.stats.total_in_service }}</span>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <span class="block text-xs uppercase tracking-wider text-slate-400 font-bold">Est. Next Wait</span>
                            <span class="text-3xl font-bold text-slate-800">{{ queue.stats.avg_wait_minutes }} m</span>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M4 7h16M4 12h16M4 17h16"/></svg>
                        </div>
                        <div>
                            <span class="block text-xs uppercase tracking-wider text-slate-400 font-bold">Next Ticket</span>
                            <span class="text-3xl font-bold text-slate-800 font-mono">{{ queue.stats.next_ticket }}</span>
                        </div>
                    </div>
                </div>

                <!-- Admin Action Panels -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Call Next / Waiting List -->
                    <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm lg:col-span-2 space-y-6">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-bold text-slate-800 flex items-center space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                                <span>Waiting Queue</span>
                            </h3>
                            <button @click="callNext" :disabled="isProcessing" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-5 py-2.5 rounded-xl transition flex items-center space-x-2 shadow-sm shadow-indigo-600/10 hover:shadow-indigo-600/20 disabled:opacity-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                                <span>Call Next Customer</span>
                            </button>
                        </div>

                        <div v-if="queue.waiting.length === 0" class="text-center py-16 border border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                            <span class="text-slate-400 text-sm">No customers currently waiting.</span>
                        </div>

                        <div v-else class="overflow-x-auto border border-slate-100 rounded-2xl">
                            <table class="min-w-full divide-y divide-slate-100 text-left">
                                <thead class="bg-slate-55/60 text-slate-500">
                                    <tr>
                                        <th class="py-3 px-4 text-xs font-bold uppercase tracking-wider">Ticket</th>
                                        <th class="py-3 px-4 text-xs font-bold uppercase tracking-wider">Customer Name</th>
                                        <th class="py-3 px-4 text-xs font-bold uppercase tracking-wider">Service</th>
                                        <th class="py-3 px-4 text-xs font-bold uppercase tracking-wider text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <tr v-for="item in queue.waiting" :key="item.id" class="hover:bg-slate-50/30 transition">
                                        <td class="py-3.5 px-4 font-mono font-bold text-indigo-600 text-base">{{ item.ticket_number }}</td>
                                        <td class="py-3.5 px-4 text-sm font-medium text-slate-800">
                                            {{ item.customer_name }}
                                            <span v-if="item.customer_phone" class="block text-[10px] text-slate-400 mt-0.5">{{ item.customer_phone }}</span>
                                        </td>
                                        <td class="py-3.5 px-4 text-sm text-slate-500 capitalize">{{ item.service_name }}</td>
                                        <td class="py-3.5 px-4 text-sm text-right space-x-1">
                                            <button @click="removeQueueItem(item.id)" class="text-xs font-bold bg-red-50 text-red-600 hover:bg-red-100 px-3 py-1.5 rounded-lg transition">Remove</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- In Service Panel -->
                    <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm space-y-6">
                        <h3 class="text-lg font-bold text-slate-800 flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span>Currently Serving</span>
                        </h3>

                        <div v-if="queue.in_service.length === 0" class="text-center py-20 border border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                            <span class="text-slate-400 text-sm">No customers currently being served.</span>
                        </div>

                        <div v-else class="space-y-4">
                            <div v-for="item in queue.in_service" :key="item.id" class="bg-slate-50 border border-slate-100 rounded-2xl p-4 space-y-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="inline-flex px-2 py-0.5 rounded-md bg-indigo-50 border border-indigo-100 text-indigo-700 font-bold font-mono text-sm">{{ item.ticket_number }}</span>
                                        <h4 class="text-sm font-bold text-slate-800 mt-2">{{ item.customer_name }}</h4>
                                        <p class="text-xs text-slate-400 mt-0.5 capitalize">{{ item.service_name }}</p>
                                    </div>
                                    <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-semibold">Serving</span>
                                </div>

                                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-200/60">
                                    <button @click="markComplete(item.id)" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2 rounded-xl transition">Complete</button>
                                    <button @click="markNoShow(item.id)" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs py-2 rounded-xl transition">No Show</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Completed Activity -->
                <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm space-y-6">
                    <h3 class="text-lg font-bold text-slate-800">Recent Activity Log</h3>

                    <div v-if="queue.completed.length === 0" class="text-center py-10 text-sm text-slate-450">
                        No activity logged today.
                    </div>

                    <div v-else class="overflow-x-auto border border-slate-100 rounded-2xl">
                        <table class="min-w-full divide-y divide-slate-100 text-left">
                            <thead class="bg-slate-55/60 text-slate-500">
                                <tr>
                                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-wider">Ticket</th>
                                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-wider">Customer</th>
                                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-wider">Service</th>
                                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-wider">Status</th>
                                    <th class="py-3 px-4 text-xs font-bold uppercase tracking-wider text-right">Completed At</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-for="item in queue.completed" :key="item.id" class="hover:bg-slate-50/20 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-slate-500">{{ item.ticket_number }}</td>
                                    <td class="py-3 px-4 text-sm font-medium text-slate-700">{{ item.customer_name }}</td>
                                    <td class="py-3 px-4 text-sm text-slate-500 capitalize">{{ item.service_name }}</td>
                                    <td class="py-3 px-4 text-sm">
                                        <span v-if="item.status === 'completed'" class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Completed</span>
                                        <span v-else class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">No Show</span>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-slate-400 text-right">{{ new Date(item.completed_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
