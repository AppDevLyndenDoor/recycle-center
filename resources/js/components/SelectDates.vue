<script setup>
import {useUserStore} from '@/store/useUserStore.js';
import {useReportStore} from '@/store/useReportStore.js';

const user = useUserStore();
const pageData = useReportStore();

const emit = defineEmits(['getEntries']);

function keymonitor(event){
    if (event.key === 'Enter') {
        event.currentTarget.blur();
    }
}


</script>

<template>
    <div class="justify-content-around">
        <div class="centered">
            <label class="centered">Between Dates</label>
        </div>
    </div>


    <div class="grid grid-cols-7 justify-content-between">
        <div class="flex-row col-span-3 justify-self-end">
            <div class="flex-row centered">
                <input v-on:keyup="keymonitor($event)" @blur="emit('getEntries')" type="date" id="DateRange1" maxlength="10" class="centered date" v-model="pageData.date1" min="1970-01-01">
            </div>
        </div>
        <div class="flex-row col-span-1 centered">
            <div class="flex-row centered">
                <p><b> - </b></p>
            </div>
        </div>
        <div class="flex-row col-span-3 justify-self-start">
            <input v-on:keyup="keymonitor($event)" @blur="emit('getEntries')" type="date" id="DateRange2" maxlength="10" class="centered date" v-model="pageData.date2" min="1970-01-01">
        </div>
    </div>
    <slot></slot>
    <div class="flex-col ml-2" v-show="(user.perms.admin)">
        <div>
            <p>{{pageData.avg}}</p>
        </div>

        <div >
            <p>{{pageData.sum}}</p>
        </div>
    </div>
    <hr>
</template>

<style scoped>

</style>
