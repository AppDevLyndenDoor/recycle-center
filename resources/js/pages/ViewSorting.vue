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
const report = useReportStore();
const state = reactive({
    sortingSettings: {
        dateRange1: '',
        dateRange2: '',
        rangeSet: false,
    },
    print: false,
    download: false,
    pendingSave: false,
    selectionAverage: 'Average:',
    selectionSum: 'Sum:',
    unitCache: [],
    offline: false,
    clickedSave: false,
})

const currentDate = computed(() => {
    const today = new Date();
    const dd = String(today.getDate()).padStart(2, '0');
    const mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
    const yyyy = today.getFullYear();
    return yyyy + '-' + mm + '-' + dd;
})
function keymonitor(event){
    if (event.key === 'Enter') {
        event.currentTarget.blur();
    }
}
function getSortingEntries(silence) {
    // Retrieves entries with timestamps between startTime and endTime inclusive
    //const obj = { generateSortingTable: generateTable }
    if (state.offline) {

        state.unitSortingCache = JSON.parse(localStorage.getItem('databaseSorting'));
        /* istanbul ignore start */
        if (!state.unitSortingCache){
            state.unitSortingCache = [];
            localStorage.setItem('databaseSorting', '[]')
        }
    } else {
        let tempUser = '';
        if (user.perms.admin) {
            tempUser = 'admin';
        } else {
            tempUser = user.pseudonym
        }
        const fields = tempUser +','+ report.date1+','+ report.date2;
        axios({
            method: 'GET',
            url: '/pickupSortingRange?fields=' + fields,

            headers: {
                "Authorization": "Bearer " + localStorage.getItem('token'),
            },
        })
            .then((response) => {
                debugger
                if (response.data.length == 0) {
                    state.unitSortingCache = JSON.parse(localStorage.getItem('databaseSorting'));
                    if (!state.unitSortingCache) {

                        state.unitSortingCache = [];
                        JSON.parse(localStorage.setItem('databaseSorting', '[]'))
                    }
                    if (!silence){
                        toasty({ mode: 'warning', message: 'No data for the selected date range',})
                    }
                    return;
                } else {
                    ;
                    state.unitSortingCache = response.data;
                    state.unitSortingCache.forEach(function (e) {
                        e.edit = [];
                    });

                    if (!state.unitSortingCache) {
                        state.unitSortingCache = [];
                        localStorage.setItem('databaseSorting', '[]');
                    }
                    localStorage.setItem('databaseSorting', JSON.stringify(state.unitSortingCache));
                }
            }, (error) => {
                toasty({ mode: 'error', response: error, request: error.request, message: error.message });
            }).finally(function () {
            //state.loaded ;
        })
    }
}
function pendingSave(pendingSave){
    debugger;
    state.pendingSave = pendingSave;
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
    if(report.date1 == '' || report.date2 == ''){
        report.date1 =  currentDate.value;
        report.date2 =  currentDate.value;
    }
    getSortingEntries(true);
})
</script>

<template>
    <div id="TemplateViewSortingEntries" v-show="(user.perms.admin)">
        <div class="no-print">

            <select-dates @get-entries="getSortingEntries(false)"></select-dates>


            <div class="justify-content-center">
                <div>
                    <h2 id="titleSortingReport">Sorting Report</h2>
                </div>

                <div>
                    <div id="saveSortingEditsButton" v-show="( state.pendingSave)" class="no-print centered">
                        <button type="button" class="btn btn-primary selBtn" @click="state.clickedSave = !state.clickedSave">Save Edits
                        </button>
                    </div>
                </div>
                <div>
                    <h2 id="titleSortingDate">{{currentDate}}</h2>
                </div>
            </div>


        </div>
    </div>
    <div class="justify-content-center">
        <div class="centered w-full">
            <div id="handsonTable" style="overflow:auto;" class="no-print">
                <handsontable :t-data="state.unitSortingCache" :selector="'viewSorting'" :print="state.print" :download="state.download" :save="state.clickedSave"
                @pendingEdits="(save) => state.pendingSave = save"></handsontable>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
