import { defineStore } from 'pinia'
import { ref } from 'vue';

export const useUserStore
    = defineStore('user', () => {
    const userName = ref('Select User');
    const pseudonym = ref('Select User');
    const perms = ref({
        admin: false,
        operator: false
    });
    const loggedIn = ref(false);
    const userNameList = ref([]);
    const maxDialog = ref('xl');
    return ({ userName, pseudonym, perms, loggedIn, userNameList, maxDialog})
})
