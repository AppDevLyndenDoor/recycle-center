import { defineStore } from 'pinia'
import { ref } from 'vue';

export const useReportStore
    = defineStore('Range', () => {
    const date1 = ref('');
    const date2 = ref('select User');
    const avg = ref('Average: ');
    const sum = ref('Sum: ');
    const maxDate = ref('')

    return ({ date1, date2, avg, sum, maxDate})
})
