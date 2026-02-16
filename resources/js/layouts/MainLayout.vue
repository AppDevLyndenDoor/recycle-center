<script setup>
import '../../css/app.css';
import '../../css/style.css';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, getCurrentInstance,  reactive,  watch, } from 'vue';
import Dialog from '@/components/Dialog.vue';
import ProductButtons from '@/components/ProductButtons.vue';
import { post_all } from '@/majax.js';


import {useOfflineStore} from '@/store/useOfflineStore.js';
import {useReportStore} from '@/store/useReportStore.js';
import { useTableStore } from '@/store/useTableStore';
import { useToastyStore } from '@/store/useToastyStore.js';
import { useUserStore } from '@/store/useUserStore';
import toastyBox from '../components/functions/toasty.vue';

document.documentElement.classList.toggle('dark', false);

const user = useUserStore();
const table = useTableStore();
const toastySettings = useToastyStore();
const offlineStore = useOfflineStore();
const report = useReportStore();

const sessionSettngs = reactive({
    version: 1,
    darkMode: false,
    selectUser: false,
    offlinePosts: [],
    page: 'entries',
    offline: true,
});
const columnHeadersPretty = [
    'User',
    'Units',
    'Uom',
    'Product',
    'Length',
    'Width',
    'Height',
    'Bin# ',
    'Date',
    'Time ',
    'Company',
    'Destination',
    'Comment',
];
watch(() => offlineStore.offlinePosts.length, () => {
    localStorage.setItem('OfflinePosts', JSON.stringify(offlineStore.offlinePosts))
})

function setHighContrast() {
    sessionSettngs.darkMode = !sessionSettngs.darkMode;
    document.documentElement.classList.toggle(
        'dark',
        sessionSettngs.darkMode,
    );
}
function logout() {
    router.post('logout');
}
watch(
    () => table.print,
    () => {
        if (!table.print) return;

        printTable(table.tData);
    },
);

function printTable(content) {
    debugger;

    const printContent = document.createElement('table');
    const tableHead = document.createElement('thead');

    for (const header of columnHeadersPretty) {
        const th = document.createElement('th');
        th.textContent = header;
        tableHead.appendChild(th);
    }
    printContent.appendChild(tableHead);
    const tableBody = document.createElement('tableBody');
    for (const element of content) {
        if (element[0] !== null) {
            // Only process rows where the first element is not null
            // Create a new row
            const row = document.createElement('tr');
            for (const x of element) {
                const cell = document.createElement('td'); // Create a cell
                cell.textContent = x; // Set the cell's text content
                row.appendChild(cell); // Add the cell to the row
            }

            tableBody.appendChild(row); // Add the row to the tbody
        }
    }
    printContent.appendChild(tableBody);
    const iframe = document.createElement('iframe');
    iframe.style.cssText = 'display: none';

    // To convert Handsontable to its pure HTML form, you can use `.toHTML()` method.
    // Remember - it's a synchronous function - if you have thousands of rows, it might freeze the browser for a while.
    iframe.srcdoc = `<!doctype html><html><head>
    <style>
        @media print {
            table {
                border-collapse: collapse;
            }
            th, td {
                border: 1px solid #ccc;
                min-width: 50px;
                padding: 2px 4px;
            }
            th {
                background-color: #f0f0f0;
                text-align: center;
                font-weight: 400;
                white-space: nowrap;
                -webkit-print-color-adjust: exact;
            }
        }
        .block-menu {
            display: none;
        }
        .print-inline {
            display: inline-block;
        }

        .print-inline span {
            border: 0px;
        }

        .print-float-right {
            float: right;
        }
        .printUnitNumberSpacer {
            padding-left: 30px;
        }

        table.out {
            width: auto;
            border: none;
        }

        table.out tbody {
            border: none;
        }

        table.out td:last-child {
            border: none;
        }
        div.customPrint > * {
            font-family: Helvetica;
            font-size:24px;
            font-weight: 500;
            line-height: 1.1;
        }
        .printBold{
            font-family: Helvetica;
            font-size:18px;
            font-weight: bold;
            line-height: 1.1;
        }
        .printSmall{
            font-family: Helvetica;
            font-size:18px;
            font-weight: 500;
            line-height: 1.1;
        }
    </style>
  </head><body>${printContent.outerHTML}</body></html>`;

    document.body.appendChild(iframe);
    iframe.onload = () => {
        iframe.contentWindow.print();
        setTimeout(() => {
            // Cleanup after printing
            document.body.removeChild(iframe);
        }, 10);
    };
    table.print = false;
}
function selectUser() {
    sessionSettngs.selectUser = true;
    //emit('selectUser');
}
function pickUser(user) {
    user.userName = user;
    sessionSettngs.selectUser = false;
}
function getUserNames() {
    axios({
        method: 'GET',
        url: '/pickupUserNames',
        headers: {
            Authorization: 'Bearer ' + localStorage.getItem('token'),
        },
    }).then(
        (response) => {
            if (response.data.length >= 0) {
                debugger;
                let userNameList = response.data[0].userNames;
                userNameList = userNameList.split(',');
                userNameList.unshift('Select User');
                // state.userNamesText =
                /* state.userNames = state.userNamesText.split(',');
             state.userNames.unshift('Select User');*/
                user.userNameList = userNameList;
                localStorage.setItem(
                    'userNames',
                    JSON.stringify(response.data),
                );
                if (!user.perms.admin) {
                    state.selectUser = true;
                }
            }
        },
        (error) => {
            if (error.message != undefined) {
                toasty({
                    mode: 'error',
                    response: error,
                    request: error.request,
                    message: error.message,
                });
            }
        },
    );
}
function ping () {
    axios({
        headers: {
            "Authorization": "Bearer " + localStorage.getItem('token'),
        },
        url: '/ping',
        type: 'GET',
    }) .then((response) => {
        if (response.data == false) {
            offlineStore.offline = true;
        }
        if (sessionSettngs.offline) {
            offlineStore.offline = false;
            post_all(offlineStore);
        }

    }, (error) => {
        if (error.message != undefined) {
            offlineStore.offline = true;
            toasty({ mode: 'error', response: error, request: error.request, message: error.message });
        }
    });

}
function submitOfflinePosts(array){
    if (array && typeof array == 'object') {
        if (array.length != 0) {
            for (const x of array) {
                const obj = {};
                Object.assign(obj, x)
                obj.headers.Authorization = "Bearer " + localStorage.getItem('token');

                obj.success = function () {};
                obj.error = function (data) { console.log(data); };
                offlineStore.offlinePosts.push(obj);
            }
            ping();
        }
    } else {
        localStorage.setItem('OfflinePosts', '[]');
    }
}
function setMaxDate() {
    const now = new Date();
    report.maxDate = now.toISOString().substring(0, 10);
}

function toasty({ mode, request, response, message }) {
    // Setting up toast notification with given parameters and a 5-second delay
    toastySettings.mode = mode;
    toastySettings.request = request;
    toastySettings.response = response;
    toastySettings.message = message;
    toastySettings.visible = true;
}
onMounted(() => {
    const instance = getCurrentInstance();
    debugger
    let name = instance.attrs.auth.user.name;
    const page = usePage();
    sessionSettngs.page = page.component;
    if (name == undefined) {
        name = 'Select User';
    }
    getUserNames();
    setMaxDate();
    if (localStorage.getItem('OfflinePosts') !== '[]'){
        submitOfflinePosts(JSON.parse(localStorage.getItem('OfflinePosts')));
    }
    //call ping every 5 minutes
    setInterval(function () { setMaxDate(); }, 300000);
    setInterval(function () {ping(); }, 300000);

    user.userName = name;
    user.pseudonym = name;
    user.perms.admin = true;
    user.perms.operator = true;
});

</script>

<template>
    <Head title="Recycle Center">
        <meta charset="utf-8" />
        <meta name="format-detection" content="telephone=no" />
        <meta name="msapplication-tap-highlight" content="no" />
        <meta name="viewport"
              content="width=device-width, initial-scale=1.0, user-scalable=yes, minimum-scale=0.5, maximum-scale=4.0">
    </Head>
    <body class="bg-white text-black dark:bg-gray-800 dark:text-white ">
    <div id="mainLayout" class="h-full bg-white text-black dark:bg-gray-800 dark:text-white ">
        <Dialog
            v-if="sessionSettngs.selectUser"
            :size="'sm'"
            :dialogVisible="sessionSettngs.selectUser"
            :title="'Select User'"
            class="fixed inset-0 z-50" >
            <div class="flex flex-wrap">
                <div v-for="user in user.userNameList" :key="user">
                    <ProductButtons
                        type="button"
                        class="btn btn-primary"
                        @click="pickUser(user)"
                        >{{ user }}</ProductButtons>
                </div>
            </div>
            <div>
                <button
                    class="btn btn-primary"
                    :class="[
                        'absolute',
                        'bottom-2',
                        'left-0',
                        'h-8',
                        'w-32',
                        'inline-flex',
                        'm-4',
                        'my-[2px]',
                        'py-[3px]',
                    ]"
                    @click="sessionSettngs.selectUser = false">
                    <p class="text-md centered relative w-full whitespace-nowrap">
                        Cancel
                    </p>
                </button>
            </div>
        </Dialog>
        <p style="font-size: 12px; position: absolute; left: 429px; top: 29px">
            v 1.{{ sessionSettngs.version }}
        </p>
        <div>
            <div >
                <div class="justify-content-between no-print  grid grid-cols-12">
                    <div class="col-span-6 mb-1">
                        <h1 id="title" class="mt-2 ml-2 text-4xl">
                            Recycle Center Tracker
                        </h1>
                    </div>
                    <!--                    <div class="flex-row centered w-full">-->
                    <div
                        class="col-span-2 col-start-8 place-content-between my-2"
                        v-show="user.perms.operator">
                        <button
                            id="syncButton"
                            type="button"
                            class="btn btn-primary place-self-center px-1"
                        @click="post_all(offlineStore)">
                            Sync: {{ offlineStore.offlinePosts.length }}
                        </button>
                    </div>
                    <div
                        class="centered col-span-3"
                        v-show="!user.perms.admin && user.perms.operator">
                        <button
                            type="button"
                            id="SelectUser"
                            class="btn btn-primary"
                            @click="selectUser()">
                            {{ user.pseudonym }}
                        </button>
                    </div>
                    <div class="col-span-1 col-start-11 my-2"
                        v-show="user.perms.admin">
                        <p class="userName centered px-1">
                            {{ user.userName }}
                        </p>
                    </div>
                    <div
                        class="username col-span-1 col-start-11 my-2"
                        v-show="!user.perms.operator">
                        <p class="userName centered px-1">
                            {{ user.userName }}
                        </p>
                    </div>
                </div>

                <div class="flex-row">
                    <div class="justify-content-between grid grid-cols-12">
                        <div class="col-span-8">
                            <button
                                type="button"
                                id="EntryButton"
                                class="btn btn-primary mx-2 px-2"
                                :class="[
                                    {'btn-primary':sessionSettngs.page !== 'Entry',
                                        'btn-success':sessionSettngs.page === 'Entry'}]"
                                @click="sessionSettngs.page = 'Entry'"
                                v-show="user.perms.operator">
                                <Link href="dashboard" method="get">
                                    Entry
                                </Link>
                            </button>

                            <button
                                type="button"
                                id="ViewEntriesButton"
                                class="btn btn-primary mx-2 px-2"
                                :class="[
                                    { 'btn-primary': sessionSettngs.page !== 'ViewEntries',
                                        'btn-success':sessionSettngs.page ==='ViewEntries', }]"
                                @click="sessionSettngs.page = 'ViewEntries'"
                                v-show="user.perms.operator" >
                                <Link href="viewEntries" method="get">
                                    View Entries
                                </Link>
                            </button>

                            <button
                                type="button"
                                id="EntrySortingButton"
                                class="btn btn-primary mx-2 px-2"
                                :class="[
                                    {'btn-primary':sessionSettngs.page !== 'Sorting',
                                    'btn-success': sessionSettngs.page === 'Sorting'}]"
                                @click="sessionSettngs.page = 'Sorting'"
                                v-show="user.perms.operator" >
                                <Link href="sorting" method="get">
                                    Sorting
                                </Link>
                            </button>
                            <button
                                type="button"
                                id="ViewSortingEntriesButton"
                                class="btn btn-primary mx-2 px-2"
                                :class="[
                                    { 'btn-primary': sessionSettngs.page !=='ViewSorting',
                                        'btn-success':sessionSettngs.page ==='ViewSorting'}]"
                                @click="sessionSettngs.page = 'ViewSorting'"
                                v-show="user.perms.operator">
                                <Link href="viewSorting" method="get">
                                    View Sorting
                                </Link>
                            </button>
                            <button
                                type="button"
                                id="SettingsButton"
                                class="btn btn-primary mx-2 px-2"
                                :class="[
                                    {'btn-primary':sessionSettngs.page !== 'Settings',
                                        'btn-success':sessionSettngs.page === 'Settings'}]"
                                @click="sessionSettngs.page = 'Settings'"
                                v-show="user.perms.admin">
                                <Link href="settings" method="get">
                                    Settings
                                </Link>
                            </button>
                        </div>
                        <div
                            class="centered col-span-1 col-start-10"
                            v-show="user.perms.operator"
                        >
                            <button
                                id="contrastSetting"
                                type="button"
                                class="btn btn-primary p-2"
                                @click="setHighContrast()"
                            >
                                <svg
                                    width="32"
                                    height="32"
                                    fill="currentColor"
                                    class="bi bi-sun"
                                    viewBox="0 0 16 16"
                                    v-show="sessionSettngs.darkMode"
                                >
                                    <path
                                        d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm0 1a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0zm0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13zm8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8zm10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0zm-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zm9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708z"
                                    />
                                </svg>
                                <svg
                                    width="32"
                                    height="32"
                                    fill="currentColor"
                                    class="bi bi-sun-fill p-2"
                                    viewBox="0 0 16 16"
                                    v-show="!sessionSettngs.darkMode"
                                >
                                    <path
                                        d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0zm0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13zm8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8zm10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0zm-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zm9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708z"
                                    />
                                </svg>
                            </button>
                        </div>
                        <div class="centered col-span-1 col-start-11">
                            <button
                                id="logout"
                                type="button"
                                class="btn btn-primary mr-2 px-1"
                                @click="logout"
                            >
                                Logout
                            </button>
                        </div>
                    </div>
                </div>

                <hr class="no-print" />
                <slot @offLinePost="addOffline()"> </slot>
                <main>
                    <table id="styledTable" hidden>
                        <caption class="centered">
                            Recycle Center Report
                        </caption>
                        <thead>
                            <tr class="colhead">
                                <th
                                    v-for="colHead in columnHeadersPretty"
                                    :key="colHead"
                                >
                                    {{ colHead }}
                                </th>
                            </tr>
                        </thead>
                        <tbody id="tbody"></tbody>
                    </table>
                </main>
            </div>
        </div>
    </div>
    <toastyBox />
    </body>
</template>

<style scoped>
input {
    font-size: 28px;
    width: 64px;
    border-width: 2px;
    border-color: black;
}
.modal-body input {
    width: 400px;
}
body {
    font-size: 100%;
    background-color: rgb(229, 236, 243);
}
.dark body {
    background-color: rgb(54, 60, 67);
}
</style>
