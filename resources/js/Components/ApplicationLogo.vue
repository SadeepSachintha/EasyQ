<template>
    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
        <!-- Main circular clock/queue base -->
        <circle cx="50" cy="46" r="32" stroke="url(#logo-grad)" stroke-width="8" stroke-linecap="round" />
        
        <!-- Inside Arrow/Play symbol representing "Next in Queue" / Service -->
        <path d="M44 34L60 46L44 58V34Z" fill="url(#logo-grad)" />
        
        <!-- Q Tail representing Queue line/scheduling direction -->
        <path d="M70 66L86 82" stroke="url(#logo-grad)" stroke-width="8" stroke-linecap="round" />
        
        <!-- Gradients -->
        <defs>
            <linearGradient id="logo-grad" x1="14" y1="14" x2="86" y2="86" gradientUnits="userSpaceOnUse">
                <stop offset="0%" stop-color="#6366f1" /> <!-- Indigo -->
                <stop offset="50%" stop-color="#3b82f6" /> <!-- Blue -->
                <stop offset="100%" stop-color="#8b5cf6" /> <!-- Purple -->
            </linearGradient>
        </defs>
    </svg>
</template>
