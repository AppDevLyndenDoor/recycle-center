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



const user = useUserStore();
const table = useTableStore();
const toastySettings = useToastyStore();
const offlineStore = useOfflineStore();
const report = useReportStore();
let cordovaMode = false;
const sessionSettings = reactive({
    version: 0,
    darkMode:  (localStorage.getItem('theme') === 'dark'),
    selectUser: false,
    offlinePosts: [],
    page: 'entries',
    offline: true,
    logoutConfirm: false,
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
    sessionSettings.darkMode = !sessionSettings.darkMode;
    document.documentElement.classList.toggle(
        'dark');
    localStorage.setItem('theme', sessionSettings.darkMode ? 'dark' : 'light');
}
function logout() {
    router.post('/logout');
}
watch(
    () => table.print,
    () => {
        if (!table.print) return;

        printTable(table.tData);
    },
);

function printTable(content) {


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
            for (let i = 0; i < element.length; i++) {
                const cell = document.createElement('td'); // Create a cell
                if(i === 8){
                    const date = new Date(element[i]);
                    const dd = String(date.getDate()).padStart(2, '0');
                    const mm = String(date.getMonth() + 1).padStart(2, '0'); //January is 0!
                    const yyyy = date.getFullYear();
                    cell.textContent =  mm + '-' + dd + '-' + yyyy;
                }
                else{
                    cell.textContent = element[i]; // Set the cell's text content
                }
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
    sessionSettings.selectUser = true;
}
function pickUser(newUser) {
    user.pseudonym = newUser;
    sessionSettings.selectUser = false;
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
                let userNameList = response.data[0].userNames;
                userNameList = userNameList.split(',');
                userNameList.unshift('Select User');
                user.userNameList = userNameList;
                localStorage.setItem(
                    'userNames',
                    JSON.stringify(response.data),
                );
                if (!user.perms.admin) {
                    sessionSettings.selectUser = true;
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
        if (sessionSettings.offline) {
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

 function checkUserPermissions() {
    axios({
        method: 'GET',
        url: '/checkUserPermissions',
        headers: {
            Authorization: 'Bearer ' + localStorage.getItem('token'),
        },
    }).then((response) => {
            if(response.data === 'admin'){
                user.perms.admin = true;
                user.perms.operator = true;
                getUserNames();
            }
            else if(response.data === 'operator'){
                user.perms.admin = false;
                user.perms.operator = true;
                getUserNames();
            }


        }, (error) => {
            toasty({ mode: 'error', response: error, request: error.request, message: error.message });
    });
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
onMounted( () => {
    if (!navigator.userAgent.toLowerCase().match('android')) {
        cordovaMode = false;
    } else {
        /* istanbul ignore start */
        const script = document.createElement('script');
        script.src = 'cordova.js';
        document.head.appendChild(script);
        cordovaMode = true;
        document.dispatchEvent(new Event('deviceready'));
    }
    if (cordovaMode) {
        const physicalScreenWidth = window.screen.width * 1.5;
        const physicalScreenHeight = window.screen.height * 1.5;
        const body = document.querySelector('body');
        if (body) {
            body.style.width = `${physicalScreenWidth}px`;
            body.style.height = `${physicalScreenHeight}px`;
            body.style.zoom = '67%';
        }
    }
    document.documentElement.classList.toggle('dark', sessionSettings.darkMode );
    const instance = getCurrentInstance();
    let name = instance.attrs.auth.user.name;
    const page = usePage();
    sessionSettings.page = page.component;
    if (name == undefined || name === 'recycle') {
        name = 'Select User';
    }
    user.userName = name;
    user.pseudonym = name;
    checkUserPermissions();

    setMaxDate();
    if (localStorage.getItem('OfflinePosts') !== '[]') {
        submitOfflinePosts(JSON.parse(localStorage.getItem('OfflinePosts')));
    }
    //call ping every 5 minutes
    setInterval(function() {
        setMaxDate();
    }, 300000);
    setInterval(function() {
        ping();
    }, 300000);
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
    <div id="mainLayout" class="h-full min-h-screen  bg-white text-black dark:bg-gray-800 dark:text-white ">
        <Dialog
            v-if="sessionSettings.selectUser"
            :size="'md'"
            :dialogVisible="sessionSettings.selectUser"
            :title="'Select User'"
            class="fixed inset-0 z-1000" >
            <div class="flex flex-wrap ml-1 justify-center">
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
                    @click="sessionSettings.selectUser = false">
                    <span class="text-md centered relative w-full whitespace-nowrap">
                        Cancel
                    </span>
                </button>
            </div>
        </Dialog>
        <Dialog v-if="sessionSettings.logoutConfirm" :size="'xs'" :dialogVisible="sessionSettings.logoutConfirm"
                :title="'Logout?'" class="fixed inset-0 z-1000" >
            <button id="CancelLogout" class="btn btn-primary" :class="[
                        'absolute',
                        'bottom-2',
                        'left-0',
                        'h-8',
                        'w-32',
                        'inline-flex',
                        'm-4',
                        'my-[2px]',
                        'py-[3px]']" @click="sessionSettings.logoutConfirm = false">
                <span class="text-md centered w-full relative whitespace-nowrap">No</span>
            </button>

                <button id="confirmLogout" class="btn btn-primary" :class="[
                    'absolute',
                    'bottom-2',
                    'right-0',
                    'h-8',
                    'inline-flex',
                    'w-32',
                    'm-4',
                    'my-[2px]',
                    'py-[3px]',
                    ]" @click="logout">
                    <span class="text-md centered w-full relative whitespace-nowrap">Yes</span>
                </button>
        </Dialog>
        <div>
            <div >
                <div class="justify-content-between no-print  grid grid-cols-12">
                    <div class="col-span-6 mb-1">
                        <h1 id="title" class="mt-2 ml-2 text-4xl">
                            Recycle Center Tracker <span style="font-size: 12px"> v 2.{{ sessionSettings.version }}</span>
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
                        class="centered col-span-1 col-start-11  mt-1 "
                        v-show="!user.perms.admin && user.perms.operator">
                        <button
                            type="button"
                            id="SelectUser"
                            class="userBtn btn-primary px-2 ml-1 h-auto text-white"
                            @click="selectUser()">
                            {{ user.pseudonym }}
                        </button>
                    </div>
                    <div class="centered col-span-1 col-start-11  mt-1"
                        v-show="user.perms.admin">
                        <p class="userName centered px-1">
                            {{ user.userName }}
                        </p>
                    </div>
                    <div
                        class="centered col-span-1 col-start-11  mt-1"
                        v-show="!user.perms.operator">
                        <p class="userName centered px-1">
                            {{ user.userName }}
                        </p>
                    </div>
                </div>

                <div class="flex-row">
                    <div class="justify-content-between grid grid-cols-12">
                        <div class="col-span-8">

                            <Link href="dashboard" method="get"
                                  class="btn mx-2 px-2 py-1"
                                  :class="[sessionSettings.page !== 'Entry' ? 'btn-primary' : 'btn-success']"
                                  @click="sessionSettings.page = 'Entry'"
                                v-show="user.perms.operator">
                                Entry
                            </Link>

                            <Link href="viewEntries" method="get"
                                  class="btn mx-2 px-2 py-1"
                                  :class="[sessionSettings.page !== 'ViewEntries' ? 'btn-primary' : 'btn-success']"
                                  @click="sessionSettings.page = 'ViewEntries'"
                                  v-show="user.perms.operator">
                                    View Entries
                            </Link>

<!--                            <Link  href="viewEntries" method="get"
                                  class="btn mx-2 px-2 py-1"
                                  :class="[sessionSettings.page !== 'Sorting' ? 'btn-primary' : 'btn-success']"
                                  @click="sessionSettings.page = 'Sorting'"
                                  v-show="user.perms.operator">
                                Sorting
                            </Link>

                            <Link  href="viewEntries" method="get"
                                  class="btn mx-2 px-2 py-1"
                                  :class="[sessionSettings.page !== 'ViewSorting' ? 'btn-primary' : 'btn-success']"
                                  @click="sessionSettings.page = 'ViewSorting'"
                                  v-show="user.perms.operator">
                                Sorting
                            </Link>-->

                            <Link href="settings" method="get"
                                  class="btn mx-2 px-2 py-1"
                                  :class="[sessionSettings.page !== 'Settings' ? 'btn-primary' : 'btn-success']"
                                  @click="sessionSettings.page = 'Settings'"
                                  v-show="user.perms.admin">
                                Settings
                            </Link>
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
                                    v-show="sessionSettings.darkMode"
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
                                    v-show="!sessionSettings.darkMode"
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
                                class="btn btn-primary mx-2 px-1"
                                @click="sessionSettings.logoutConfirm = true"
                            >
                                Logout
                            </button>
                        </div>
                    </div>
                </div>

                <hr class="no-print dark:bg-gray-300" />
                <slot> </slot>
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
