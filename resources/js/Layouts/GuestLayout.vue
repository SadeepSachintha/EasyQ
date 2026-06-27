<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center pt-6 sm:justify-center sm:pt-0 relative overflow-hidden"
        style="background: radial-gradient(circle at 50% 50%, #f8fafc 0%, #e2e8f0 100%);"
    >
        <!-- Background decorative glows -->
        <div class="absolute top-1/4 left-1/4 w-[350px] h-[350px] bg-indigo-300/20 rounded-full blur-[80px] pointer-events-none"></div>
        <div class="absolute bottom-1/4 right-1/4 w-[350px] h-[350px] bg-blue-300/20 rounded-full blur-[80px] pointer-events-none"></div>

        <div class="z-10 flex flex-col items-center">
            <Link href="/" class="transition transform hover:scale-105 duration-300">
                <div class="w-20 h-20 bg-white rounded-2xl shadow-xl border border-slate-100 flex items-center justify-center p-2.5">
                    <ApplicationLogo class="w-full h-full" />
                </div>
            </Link>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-800 mt-4">EasyQ Portal</h1>
            <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-widest font-bold">Real-Time Queue Console</p>
        </div>

        <div
            class="mt-8 w-full overflow-hidden bg-white/70 backdrop-blur-xl border border-white/80 px-8 py-8 shadow-2xl sm:max-w-md sm:rounded-3xl z-10 transition duration-300 hover:shadow-indigo-500/10"
        >
            <slot />
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-xs text-slate-500 z-10">
            <p>EasyQ is a product of <a href="https://ss-labz.pages.dev/" target="_blank" rel="noopener noreferrer" class="hover:text-indigo-600 transition-colors underline font-medium">SS Labz</a></p>
        </div>
    </div>
</template>
