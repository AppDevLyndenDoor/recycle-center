<script setup lang="ts">
import { PublicClientApplication } from '@azure/msal-browser';
const instance = ref(getCurrentInstance());
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, getCurrentInstance, onMounted } from 'vue';
import Checkbox from '@/components/Checkbox.vue';
import GenericButton from '@/components/GenericButton.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/InputLabel.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import TextInput from '@/components/TextInput.vue';
import GuestLayout from '@/layouts/settings/GuestLayout.vue';

const serviceSelection = ref(0);
const clientId = ref(import.meta.env.VITE_AZURE_AD_CLIENT_ID);
const authority = ref(
    import.meta.env.VITE_AZURE_AD_AUTHORITY ||
        'https://login.microsoftonline.com/' +
            import.meta.env.VITE_AZURE_AD_TENANT_ID,
);
const redirectUri = ref(
    import.meta.env.VITE_AZURE_AD_REDIRECT_URI ||
        `${window.location.origin}/api/auth/azure`,
);
const logoutRedirectUri = ref(`${window.location.origin}/login`);
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
const csrfToken =
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ??
    String(instance.value?.attrs?.csrf_token ?? '');

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
            responseType: 'code',
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
        const accessTokenResponse =
            await msalInstance.acquireTokenPopup(loginRequest);
        const token =
            accessTokenResponse.idToken || accessTokenResponse.accessToken;

        if (!token) {
            throw new Error('Azure login did not return a token.');
        }

        const response = await fetch('/api/auth/azure/callback', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            },
            body: JSON.stringify({ code: token }),
        });

        if (!response.ok) {
            const payload = await response.json().catch(() => null);
            const details =
                payload?.message ||
                payload?.errors?.email?.[0] ||
                payload?.errors?.code?.[0] ||
                'Unknown callback error';

            throw new Error(
                `Azure callback failed with status ${response.status}: ${details}`,
            );
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
async function logout() {
    const msalConfig = {
        auth: {
            clientId: clientId.value,
            authority: authority.value,
            redirectUri: logoutRedirectUri.value,
            postLogoutRedirectUri: logoutRedirectUri.value,
        },
        cache: {
            cacheLocation: 'localStorage',
        },
    };

    const msalInstance = new PublicClientApplication(msalConfig);

    const account =
        msalInstance.getActiveAccount() || msalInstance.getAllAccounts()[0];
    if (account) {
        await msalInstance.logoutRedirect({
            account,
            postLogoutRedirectUri: `${window.location.origin}/logout`,
        });
    }
}*/
onMounted(() => {
    //logout();
});
</script>

<template>
    <Head title="Log in" />

    <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
        {{ status }}
    </div>

    <div @submit.prevent="submit" class="h-72 text-black">
        <div v-if="serviceSelection == 0">
            <div>
                <GenericButton
                    id="microsoftButton"
                    class="mx-auto mb-2 h-12 w-full pt-1"
                    @clicked="login()"
                >
                    <div class="mr-4 mb-1 inline-flex">
                        <font-awesome-icon
                            :icon="['fa-brands', 'microsoft']"
                            :class="[
                                'w-6',
                                'h-6',
                                'm-2',
                                'mr-4',
                                'top-0',
                                'text-xl',
                                'dark:text-zinc-200',
                                'text-zinc-200',
                            ]"
                        />
                        <p class="mt-[6px] text-center text-xl">
                            Microsoft Account
                        </p>
                    </div>
                </GenericButton>
                <GenericButton
                    id="sharedAccount"
                    class="mx-auto h-12 w-full pt-1"
                    @clicked="serviceSelection = 1"
                >
                    <div class="mr-4 mb-1 inline-flex">
                        <font-awesome-icon
                            :icon="['fas', 'users']"
                            :class="[
                                'w-6',
                                'h-6',
                                'm-2',
                                'mr-4',
                                'top-0',
                                'text-xl',
                                'dark:text-zinc-200',
                                'text-zinc-200',
                            ]"
                        />
                        <p class="mt-[6px] text-center text-xl">
                            Shared Account
                        </p>
                    </div>
                </GenericButton>
                <div
                    class="mt-2 rounded-md bg-slate-200 p-4 text-sm dark:bg-slate-800 dark:text-white"
                >
                    <p class="text-center">
                        Microsoft account is for individual users.
                    </p>
                    <p class="text-center">
                        Shared Account is for users operating on behalf of a
                        location (i.e Building D, Building 11, etc.)
                    </p>
                </div>
            </div>
        </div>
        <div v-if="serviceSelection == 1">
            <form>
                <div class="">
                    <InputLabel for="email" value="Email" />

                    <TextInput
                        id="email"
                        type="text"
                        class="mt-1 block w-full place-self-start bg-slate-200 pl-2 dark:bg-slate-700 dark:text-slate-100"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="mt-4">
                    <InputLabel for="password" value="Password" />

                    <TextInput
                        @input="keyChecker({ event: $event })"
                        id="password"
                        type="password"
                        class="mt-1 block w-full bg-slate-200 pl-2 dark:bg-slate-700 dark:text-slate-100"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                    />

                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="mt-4 inline-flex w-full justify-between">
                    <label class="flex items-center">
                        <Checkbox
                            name="remember"
                            v-model:checked="form.remember"
                        />
                        <span
                            class="ml-2 text-sm text-gray-600 dark:text-slate-100"
                            >Remember me</span
                        >
                    </label>
                    <!--                        <Link v-if="canResetPassword" :href="'password.request'"-->
                    <!--                              class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:text-white">-->
                    <!--                            Forgot your password?-->
                    <!--                        </Link>-->
                </div>

                <div class="mx-auto mt-4 inline-flex w-full">
                    <PrimaryButton
                        id="Login"
                        :class="[
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
                            { 'opacity-25': form.processing },
                        ]"
                        :disabled="form.processing"
                    >
                        Log in
                    </PrimaryButton>
                </div>
            </form>
            <GenericButton
                :class="[
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
                ]"
                @clicked="serviceSelection = 0"
            >
                <p class="text-center">Go back</p>
            </GenericButton>
        </div>
    </div>
</template>
