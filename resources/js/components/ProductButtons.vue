<script setup lang="ts">
const emit = defineEmits(['clicked']);
const props = defineProps({
    disabled: Boolean,
    active: Boolean,
})


function clicked(){
    if(props.disabled) return;
    emit('clicked');
}
</script>


<template>
    <button type="button" class="btn selBtn" :class="[{'btn-primary': !(active || disabled), 'btn-success': (active && !disabled), 'btn-primary disabled': disabled}]" @click="clicked"><slot/></button>
</template>

<style scoped>

</style>
