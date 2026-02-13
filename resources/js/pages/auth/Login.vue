<script setup lang="ts">

import { PublicClientApplication } from '@azure/msal-browser';
const instance = ref(getCurrentInstance());
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {  Head,useForm, router } from '@inertiajs/vue3';
import { ref, getCurrentInstance } from 'vue';
import Checkbox from '@/components/Checkbox.vue';
import GenericButton from '@/components/GenericButton.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/InputLabel.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import TextInput from '@/components/TextInput.vue';
import GuestLayout from '@/layouts/settings/GuestLayout.vue';

const serviceSelection = ref(0);
const clientId = ref(import.meta.env.VITE_AZURE_AD_CLIENT_ID);
const authority = ref('https://login.microsoftonline.com/' + import.meta.env.VITE_AZURE_AD_TENANT_ID);
const redirectUri = ref('https://' + import.meta.env.VITE_FRONTEND_HOST + '/api/auth/azure');
defineOptions({ layout: GuestLayout });

defineProps<{
    status?: string;
    //canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});
const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute('content') ?? String(instance.value?.attrs?.csrf_token ?? '');

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
async function login() {
    const msalConfig = {
        auth: {
            clientId: clientId.value,
            authority: authority.value,
            redirectUri: redirectUri.value,
            responseType: 'code'
        },
        cache: {
            cacheLocation: 'localStorage',
        },
    };

    const msalInstance = new PublicClientApplication(msalConfig);

    const loginRequest = {
        scopes: ['openid', 'profile', 'email'],
    };

    try {
        const accessTokenResponse = await msalInstance.acquireTokenPopup(loginRequest);
        const accessToken = accessTokenResponse.accessToken;
        const response = await fetch('/api/auth/azure/callback', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
            },
            body: JSON.stringify({ code: accessToken }),
        });

        if (!response.ok) {
            throw new Error(`Azure callback failed with status ${response.status}`);
        }

        router.get('/dashboard');
    } catch (error) {
        console.error(error);
    }
}
function keyChecker({ event }: { event: any }) {
    if (event.key === 'Enter') {
        submit();
    }
}
function anonUser() {
    form.email = 'anon@anon.com';
    form.password = 'test';
    submit();
}
</script>

<template>

        <Head title="Log in" />

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <div @submit.prevent="submit" class="text-black h-72">
            <div v-if="serviceSelection == 0">
                <div>
                    <GenericButton id="microsoftButton" class="mx-auto pt-1 h-12 w-full mb-2" @clicked="login()">
                        <div class="inline-flex mr-4 mb-1">
                            <font-awesome-icon :icon="['fa-brands', 'microsoft']" :class="[
            'w-6',
            'h-6',
            'm-2',
            'mr-4',
            'top-0',
            'text-xl',
            'dark:text-zinc-200',
            'text-zinc-200',
        ]" />
                            <p class="text-center text-xl mt-[6px]">Microsoft Account</p>
                        </div>
                    </GenericButton>
                    <GenericButton id="sharedAccount" class="mx-auto pt-1 h-12 w-full" @clicked="serviceSelection = 1">
                        <div class="inline-flex mr-4 mb-1">
                            <font-awesome-icon :icon="['fas', 'users']" :class="[
            'w-6',
            'h-6',
            'm-2',
            'mr-4',
            'top-0',
            'text-xl',
            'dark:text-zinc-200',
            'text-zinc-200',
        ]" />
                            <p class="text-center text-xl mt-[6px]">Shared Account</p>
                        </div>
                    </GenericButton>
                    <GenericButton id="anonAccount" class="mx-auto pt-1 h-12 w-full mt-2" @clicked="anonUser()">
                        <div class="inline-flex mr-4 mb-1">
                            <font-awesome-icon :icon="['fas', 'binoculars']" :class="[
            'w-6',
            'h-6',
            'm-2',
            'mr-4',
            'top-0',
            'text-xl',
            'dark:text-zinc-200',
            'text-zinc-200',
        ]" />
                            <p class="text-center text-xl mt-[6px]">View Only</p>
                        </div>
                    </GenericButton>
                    <div class="p-4 rounded-md dark:bg-slate-800 dark:text-white bg-slate-200 text-sm mt-2">
                        <p class="text-center">Microsoft account is for individual users.</p>
                        <p class="text-center">Shared Account is for users operating on behalf of a location (i.e
                            Building D, Building 11, etc.)</p>
                        <p class="text-center">"View Only" doesn't allow editing.</p>
                    </div>
                </div>
            </div>
            <div v-if="serviceSelection == 1">
                <form>
                    <div class="">
                        <InputLabel for="email" value="Email"/>

                        <TextInput id="email" type="email"
                                   class="pl-2 mt-1 block w-full place-self-start dark:text-slate-100 dark:bg-slate-700 bg-slate-200"
                                   v-model="form.email" required autofocus autocomplete="username" />

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="password" value="Password" />

                        <TextInput @input="keyChecker({event : $event})" id="password" type="password"
                                   class="pl-2 mt-1 block w-full dark:text-slate-100 dark:bg-slate-700 bg-slate-200"
                                   v-model="form.password" required autocomplete="current-password" />

                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="inline-flex justify-between w-full mt-4">
                        <label class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.remember" />
                            <span class="dark:text-slate-100 ml-2 text-sm text-gray-600">Remember me</span>
                        </label>
<!--                        <Link v-if="canResetPassword" :href="'password.request'"-->
<!--                              class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:text-white">-->
<!--                            Forgot your password?-->
<!--                        </Link>-->
                    </div>

                    <div class="inline-flex w-full mt-4 mx-auto">
                        <PrimaryButton id="Login" :class="[
            'relative',
            'top-8',
            'left-[300px]',
            'ml-4',
            'dark:text-white',
            'dark:hover:bg-slate-500',
            'dark:bg-slate-700',
            'rounded-lg',
            'bg-blue-500',
            'text-white',
            'h-8',
            'hover:bg-blue-600',
            'active:bg-blue-600',
            'focus:outline-none',
            'focus:ring',
            'focus:ring-blue-400',
            { 'opacity-25': form.processing }
        ]" :disabled="form.processing">
                            Log in
                        </PrimaryButton>
                    </div>
                </form>
                <GenericButton :class="[
            'dark:text-white',
            'dark:hover:bg-slate-500',
            'dark:bg-slate-700',
            'rounded-lg',
            'bg-blue-500',
            'text-white',
            'h-8',
            'w-20',
            'hover:bg-blue-600',
            'active:bg-blue-600',
            'focus:outline-none',
            'focus:ring',
            'focus:ring-blue-400',
        ]" @clicked="serviceSelection = 0">
                    <p class="text-center">Go back</p>
                </GenericButton>
            </div>
        </div>
</template>
