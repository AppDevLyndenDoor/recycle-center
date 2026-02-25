<script setup>
import {HotTable} from '@handsontable/vue3';
import axios from 'axios';
import Handsontable from 'handsontable';
import { getCurrentInstance, ref, onMounted, nextTick, inject, watch, reactive } from 'vue';
import { registerAllModules } from 'handsontable/registry';
import 'handsontable/dist/handsontable.full.css';
import { post_to_server } from '@/majax.js';
import { useOfflineStore} from '@/store/useOfflineStore.js';
import { useReportStore } from '@/store/useReportStore.js';
import {useTableStore} from '@/store/useTableStore'
import { useToastyStore} from '@/store/useToastyStore.js';


const pageData = useReportStore();
const printTable = useTableStore();
const toastySettings = useToastyStore();
const offlineStore = useOfflineStore();

const props = defineProps(['tData', 'selector','print', 'download','save']);
const emit = defineEmits(['pendingEdits']);
const instance = getCurrentInstance();
const settings = ref({});
const tableData = ref(props.tData);

const tableSortingSettings = {
    columnHeaders: [
        'user',
        'units',
        'product',
        'date',
        'picked_timestamp',
        'company',
    ],
        schema: {
        id: 0,
            user: '',
            units: '',
            product: '',
            date: '',
            picked_timestamp: '',
            company: '',
            edit: []
    },
    columnHeadersPretty: [
        'User',
        'Units',
        'Product',
        'Date',
        'Time ',
        'Company',
    ],
    columnNames: [
        { data: 'user', editor: false },
        { data: 'units' },
        { data: 'product' },
        { data: 'date', type: 'date', dateFormat: 'MM/DD/YYYY' },
        { data: 'picked_timestamp', editor: false },
        { data: 'company' },
    ],
}
const tableEntriesSettings = {
    columnHeaders: [
        'user',
        'units',
        'uom',
        'product',
        'length',
        'width',
        'height',
        'bin',
        'date',
        'picked_timestamp',
        'company',
        'destination',
        'comment',
    ],
    schema: {
        id: 0,
        user: '',
        units: '',
        uom: '',
        product: '',
        length: '',
        width: '',
        height: '',
        bin: '',
        date: '',
        picked_timestamp: '',
        company: '',
        destination: '',
        comment: '',
        edit: []
    },
    columnNames: [
        { data: 'user', editor: false },
        { data: 'units' },
        { data: 'uom' },
        { data: 'product' },
        { data: 'length' },
        { data: 'width' },
        { data: 'height' },
        { data: 'bin' },
        { data: 'date', type: 'date', dateFormat: 'MM/DD/YYYY' },
        { data: 'picked_timestamp', editor: false },
        { data: 'company' },
        { data: 'destination' },
        { data: 'comment' },
    ],
}
const pendingEdits = ref([])
watch(() => props.tData, (newVal) => {
    if(newVal == null){
        //newVal = (props.selector == 'viewEntry') ? tableEntriesSettings.schema : tableSortingSettings.schema;
        newVal = tableEntriesSettings.schema
    }
    tableData.value = newVal;
    settings.value = tableSettings();
    const ht = instance.refs.hotTableComponent.hotInstance
    ht.updateSettings(settings.value);
})
watch(() => props.print, () => {
        printReport();
})
watch(() => props.download, () => {
    downloadCSV();
})
watch(() => props.save, () => {
    SaveEdits();
})
function tableSettings () {
    let columnHeaders = [];
    let columnNames = [];
    let avgText = 'Units Average: '
    if(props.selector == 'viewEntry') {
        columnHeaders =  tableEntriesSettings.columnHeaders;
        columnNames = tableEntriesSettings.columnNames;
        //schema = tableEntriesSettings.schema;

    } else {
        columnHeaders = tableSortingSettings.columnHeaders;
        columnNames = tableSortingSettings.columnNames;
        //schema = tableSortingSettings.schema;
    }

    return {
        licenseKey: 'non-commercial-and-evaluation',
        rowHeaders: true,
        stretchH: 'all',
        rowHeights: '20px',
        height: 800,
        data: tableData,
        columns: columnNames,
        columnSorting: true,
        sortIndicator: true,
        filters: true,
        dropdownMenu: true,
        manualColumnResize: true,
        colHeaders: columnHeaders,
        minSpareRows: 1,
        contextMenu: {
            callback: function (key, options, event) {
              if(key == 'RemoveRow'){
                  const hot = instance.refs.hotTableComponent.hotInstance;
                  const selected = hot.getSelected()[0][0];
                  const rowData = hot.getSourceDataAtRow(selected);
                 for(let i = 0; i < tableData.value.length-1; i++) {
                     if(tableData.value[i]['id'] === rowData['id']){
                         tableData.value.slice(i,i);
                         break;
                     }
                 }
                  rowData.changes = ['removeRow'];
                  pendingEdits.value.push(rowData);
                  hot.alter('remove_row', selected);
                  emit('pendingEdits', true);
              }
            },
            items: {
                "RemoveRow": {
                    name: 'Remove Row' // Set custom text for predefined option
                },
            }
        },
        afterSelection: function (row, col, row2, col2, selectionLayerLevel) {
            if(row == -1) return;
            const data = [];
            const hot = instance.refs.hotTableComponent.hotInstance
            /* istanbul ignore start */

            // if selection is inverted, revert
            if (row > row2){
                const v = row;
                row = row2;
                row2 = v;
            }
            if (col != col2){
                pageData.avg = '';
                pageData.sum = 'Must select single column';
                return
            }
            let colName = 'Units Sum: ';
            if(props.selector == 'viewEntry') {
                avgText = ' Average: '
                //push data into array
                if (col != 4 || col != 5 || col != 6) {
                    col = 1;
                }
                //turns values into floats
                switch (col) {
                    case 1:
                        colName = 'Units';
                        break;
                    case 4:
                        colName = 'Length';
                        break;
                    case 5:
                        colName = 'Width';
                        break;
                    case 6:
                        colName = 'Height';
                        break;
                }
            }else if (col != 1) {
                    col = 1;
            }
            for (let i = row; i <= row2; ++i) {
                data.push(hot.getDataAtCell(i, col));
            }
            const result = data.map(function (x) {
                return parseFloat(x);
            });
            if(isNaN(result[0])) return
            // Sum the selection
            let tempSum = result.reduce((a, b) => a + b, 0);
            // reduce to 1 decimal place
            tempSum = colName + Math.round(tempSum * 10) / 10;
            pageData.sum = tempSum;
            // Average the selection
            let total = 0;
            for (let i = 0; i<result.length; ++i){
                total += result[i];
            }
            let tempAvg = total / result.length;
            // reduce to 1 decimal place
            tempAvg = avgText + Math.round(tempAvg*10)/10;
            pageData.avg = tempAvg;
        },
        afterChange: function (changes, source) {

            if (source === 'edit') {

                const ht = instance.refs.hotTableComponent.hotInstance;
                for (let i = 0; i < changes.length; i++) {
                    const allData = ht.getSourceDataAtRow(changes[i][0]);
                    allData.changes = changes;
                    pendingEdits.value.push(allData);
                }
                emit('pendingEdits', true);
            }
        }
    }
}
function SaveEdits(){

    const url = (props.selector == 'viewEntry') ? '/saveEntriesEdits' : '/saveSortingEdits';
    post_to_server({
        headers: {
            "Authorization": "Bearer " + localStorage.getItem('token'),
        },
        url: url,
        method: 'POST',
        data: pendingEdits.value,
        success: function (data, status) {
            if (!data.result) {

                pendingEdits.value = [];
                //emit('pendingEdits', false);
                toasty({ mode: 'success', message: 'Saved Edits' });
            }
        },
        error: function (data) {
            toasty({ mode: 'warning', message: "Couldn't Save Edits"});
        },
        complete: function () { },
    }, offlineStore);
    localStorage.setItem('database', JSON.stringify(tableData.value));
    emit('pendingEdits', false);
    /*axios({
        method: 'POST',
        url: url,
        data: pendingEdits.value,
        headers: {
            "Authorization": "Bearer " + localStorage.getItem('token'),
        },
    }).then((response) => {
            pendingEdits.value = [];
            emit('pendingEdits', false);
            //const hot = instance.refs.hotTableComponent.hotInstance;
            //hot.reset()
        }, (error) => {
        toasty({ mode: 'error', response: error, request: error.request, message: error.message });
    })*/

}

function filterTableData(){
    const hot = instance.refs.hotTableComponent.hotInstance;
    let table = hot.getSourceData();
    // Filter out rows that are completely empty
    table = table.filter((a) => {
        return Object.values(a).join('').trim().length > 0;
    });

    return table;
}

function printReport() {

    const hot = instance.refs.hotTableComponent.hotInstance;
    printTable.tData = hot.getData();
    printTable.print = true;
}
function downloadCSV() {
    const hot = instance.refs.hotTableComponent.hotInstance;
    const exportPlugin = hot.getPlugin('exportFile');
    exportPlugin.downloadFile('csv', {
        columnHeaders: true,
        filename: 'Recycle Center Report [YYYY]-[MM]-[DD]',
    });
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
    settings.value = tableSettings();
    const ht = instance.refs.hotTableComponent.hotInstance
    ht.updateSettings(settings.value);
    filterTableData();
})

</script>

<template>
    <hot-table ref="hotTableComponent" class="m-3 data-sheet" :settings="settings" :data="tableData"  :instance="instance"></hot-table>
</template>

<style scoped>

</style>
