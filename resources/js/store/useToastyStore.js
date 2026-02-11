import { defineStore } from 'pinia'
import { ref } from 'vue';

export const useToastyStore
    = defineStore('toastySettings', () => {
        const mode = ref('');
        const request = ref('');
        const response = ref('');
        const message = ref('');
        const delay = ref(5000);
        const visible = ref(false);

    return ({ mode, request, response, message, delay, visible })
})
