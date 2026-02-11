<template>
  <button :disabled="props.disable" :class="[
    'text-white',
    'rounded-lg',
    'text-white',
    'dark:text-zinc-200',
    {
      'bg-slate-400': props.disable,
      'bg-blue-500': (!props.alternate && !props.disable),
      'dark:bg-slate-700': (!props.alternate && !props.disable),
      'text-white': (!props.alternate && !props.disable),
      'hover:bg-blue-400': (!props.alternate && !props.disable),
      'dark:hover:bg-slate-500': (!props.alternate && !props.disable),

      'bg-blue-400': (props.alternate && !props.disable),
      'dark:bg-slate-600': (props.alternate && !props.disable),
      'text-white': (props.alternate && !props.disable),
      'hover:bg-blue-300': (props.alternate && !props.disable),
      'dark:hover:bg-slate-400': (props.alternate && !props.disable),
    },

     ]" @click="clicked()">
    <slot/>
  </button>
</template>

<script setup>
const emit = defineEmits(['clicked']);
const props = defineProps({
  disable: Boolean,
  alternate: Boolean,
})
function clicked(){
  emit('clicked');
}
</script>
