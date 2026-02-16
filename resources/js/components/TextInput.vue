<script setup>
import { onMounted, ref } from 'vue';

defineProps(['modelValue']);

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (
        input.value.hasAttribute('autofocus')
        && (!document.activeElement || document.activeElement === document.body)
    ) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        :class="[
        'border-gray-300',
        'focus:border-indigo-500',
        'focus:ring-indigo-500',
        'rounded-md',
        'shadow-sm',
        'pl-2',
        'dark:bg-slate-700',
        'bg-slate-200',
        'dark:text-gray-300',
        'text-lg',
        'w-full'
        ]"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        ref="input"
    />
</template>
