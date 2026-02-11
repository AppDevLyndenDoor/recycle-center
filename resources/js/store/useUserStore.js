import { defineStore } from 'pinia'
import { ref } from 'vue';

export const useUserStore
    = defineStore('user', () => {
    const userName = ref('select User');
    const pseudonym = ref('select User');
    const perms = ref({
        admin: false,
        operator: false
    });
    const loggedIn = ref(false);
    const userNameList = ref([]);
    return ({ userName, pseudonym, perms, loggedIn, userNameList})
})
