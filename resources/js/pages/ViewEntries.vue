<script setup>
import axios from 'axios';
import { computed, onMounted, reactive } from 'vue';
import Handsontable from '@/components/Handsontable.vue';

import SelectDates from '@/components/SelectDates.vue';
import {useReportStore} from '@/store/useReportStore.js';
import {useToastyStore} from '@/store/useToastyStore.js';
import {useUserStore} from '@/store/useUserStore';

const user = useUserStore();
const toastySettings = useToastyStore();
const range = useReportStore();

const state = reactive({
    showPickUser: false,
    selectionSum: 'Sum:',
    selectionAverage: 'Average:',
    reportSettings: {
        dateRange1: '',
        dateRange2: '',
        rangeSet: false,
    },
    pendingSave: false,
    clickedSave: false,
    offline: false,
    unitCache: [],
    userName: (user.userName == '') ?  'Select User' :user.userName,
    print: false,
    download: false,
})

const currentDate = computed(() => {
    const today = new Date();
    const dd = String(today.getDate()).padStart(2, '0');
    const mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
    const yyyy = today.getFullYear();
    return yyyy + '-' + mm + '-' + dd;
})
function getEntries(silence) {

    // Retrieves entries with timestamps between startTime and endTime inclusive
    //const startTime = range.date1;
    //const endTime = range.date2;
    if (state.offline) {
        if (localStorage.getItem('database') == null) {
            const model = [];
            localStorage.setItem('database', JSON.stringify(model));
            state.unitCache = model;
        } else {
            state.unitCache = JSON.parse(localStorage.getItem('database'));
        }
    } else {
        let tempUser = '';
        if (user.perms.admin) {
            tempUser = 'admin';
        } else {
            tempUser = user.pseudonym
        }
        const fields = tempUser +','+range.date1+','+range.date2;

        axios({
            method: 'GET',
            url: '/pickupUnitRange?fields=' + fields,
            headers: {
                "Authorization": "Bearer " + localStorage.getItem('token'),
            },
        }) .then((response) => {
            if (response.data.length == 0) {

                state.unitCache = JSON.parse(localStorage.getItem('database'));

                if (!silence) {
                    toasty({ mode: 'warning', response: 'No data for the selected date range',})
                }
            } else {
                state.unitCache = response.data;
                localStorage.setItem('database', JSON.stringify(state.unitCache));
            }
        }, (error) => {

            toasty({ mode: 'error', response: error, request: error.request, message: error.message });
        });
    }
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
    if(range.date1 == '' || range.date2 == ''){
        range.date1 =  currentDate.value;
        range.date2 =  currentDate.value;
    }

    getEntries(true);
});

</script>

<template>

    <div id="viewEntries">
    </div>
        <div id="TemplateViewEntries">
            <div class="no-print">

                <select-dates @get-entries="getEntries(false)">
                <div class="grid grid-cols-4 justify-content-between" v-show="(user.perms.admin)" style="margin-top:20px;">
                    <div class="col-start-2 centered">
                            <button id="PrintButton" type="button" class="btn btn-primary print centered p-2" @click='state.print = !state.print'>Print</button>
                    </div>
                    <div class="col-start-3 centered">

                        <button id="DownloadButton" type="button" class="btn btn-primary download centered lrgBtn p-2" @click="state.download = !state.download">Download</button>
                    </div>
                </div>
                </select-dates>

                <div class="grid grid-cols-3 justify-content-between">
                    <div class="">
                        <h2 id="titleReport">Recycle Center Report</h2>
                    </div>

                    <div class="col">
                        <div id="saveEditsButton" v-show="(state.pendingSave)" class="col no-print centered">
                            <div>
                                <div id="saveSortingEditsButton" v-show="( state.pendingSave)" class="col no-print centered">
                                    <button type="button" class="btn btn-primary selBtn" @click="state.clickedSave = !state.clickedSave">Save Edits
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="">
                        <h2 id="titleDate">{{currentDate}}</h2>
                    </div>
                </div>
            </div>
        </div>
    <div class="row justify-content-center">
        <div class="col centered w-full">
            <div id="handsonTable" style="overflow:auto;" class="no-print ">
                <p v-if="user.perms.admin" style="width:60%;margin:auto">To delete a row, right click a row and select "Remove Row."</p>
                <handsontable :t-data="state.unitCache" :selector="'viewEntry'" :print="state.print" :download="state.download"
                              :save="state.clickedSave" @pendingEdits="(save) => state.pendingSave = save"></handsontable>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
