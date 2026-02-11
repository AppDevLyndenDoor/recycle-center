<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button
        :type="type"
        :class="[
        'inline-flex',
        'items-center',
        'px-4',
        'py-2',
        'border',
        'border-transparent',
        'rounded-md',
        'font-semibold',
        'text-xs',
        'text-white',
        'uppercase',
        'tracking-widest',
        'focus:bg-gray-700',
        'active:bg-gray-900',
        'focus:outline-none',
        'focus:ring-2',
        'focus:ring-indigo-500',
        'focus:ring-offset-2',
        'transition',
        'ease-in-out',
        'duration-150',
        'dark:text-zinc-200',
        'bg-blue-500',
        'hover:bg-blue-400',
        'dark:hover:bg-slate-500',
        'dark:bg-slate-700',
        ]"
    >
        <slot />
    </button>
</template>
