import { defineStore } from 'pinia'
import { ref } from 'vue';

export const useOfflineStore
    = defineStore('offline', () => {
    const offlinePosts = ref([]);
    const offline = ref(false)
    return ({ offlinePosts, offline })
})
