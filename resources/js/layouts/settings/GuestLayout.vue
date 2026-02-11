<script setup>
//import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div :class="[
    'min-h-screen',
    'flex',
    'flex-col',
    'sm:justify-center',
    'items-center',
    'pt-6',
    'sm:pt-0',
    'dark:bg-gray-900',
    'bg-gray-100',
    ]">
        <div>
            <h1 class='dark:text-white text-black text-2xl'>Login</h1>
        </div>

        <div
            class="w-full sm:max-w-md mt-6 px-6 py-4 bg-gray-300 dark:bg-gray-600 shadow-md overflow-hidden sm:rounded-xl"
        >
            <slot />
        </div>
    </div>
</template>
