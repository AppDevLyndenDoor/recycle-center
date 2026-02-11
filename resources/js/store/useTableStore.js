import { defineStore } from 'pinia'
import { ref } from 'vue';

export const useTableStore
    = defineStore('table', () => {
    const tData = ref('');
    const print = ref(false);

    return ({ tData, print })
})
