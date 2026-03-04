<script setup>
import { HotTable } from '@handsontable/vue3';
import Handsontable from 'handsontable';
import { getCurrentInstance, onMounted, ref, watch } from 'vue';
import 'handsontable/dist/handsontable.full.css';
import { post_to_server } from '@/majax.js';
import { useOfflineStore } from '@/store/useOfflineStore.js';
import { useReportStore } from '@/store/useReportStore.js';
import { useTableStore } from '@/store/useTableStore';
import { useToastyStore } from '@/store/useToastyStore.js';
import { useUserStore } from '@/store/useUserStore.js';

const pageData = useReportStore();
const printTable = useTableStore();
const toastySettings = useToastyStore();
const offlineStore = useOfflineStore();
const user = useUserStore();

const props = defineProps(['tData', 'selector', 'print', 'download', 'save']);
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
        edit: [],
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
        {
            data: 'date',
            type: 'date',
            readOnly: true,
            dateFormat: 'MM/DD/YYYY',
        },
        { data: 'picked_timestamp', editor: false },
        { data: 'company' },
    ],
};
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
        edit: [],
    },
    columnNames: [
        { data: 'user', editor: false },
        { data: 'units', type: 'numeric',
            numericFormat: {
                pattern: '0.0000',
            },
            allowInvalid: false,
        },
        { data: 'uom',
            type: 'dropdown',
            source: ['each', 'yards'],
            strict: true,

            allowInvalid: false,
        },
        { data: 'product' },
        { data: 'length',type: 'numeric',
            numericFormat: {
                pattern: '0.00',
            },
            allowInvalid: false,},
        { data: 'width' },
        { data: 'height'},
        { data: 'bin' },
        { data: 'date', 'renderer': 'customDateRendererGate',editor: 'date', dataType: 'date', dateFormat: 'MM/DD/YYYY' },
        { data: 'picked_timestamp'  ,editor: false },
        { data: 'company',
            type: 'dropdown',
            source: ['Lynden Door', 'Victory Millwork', 'LD Trucking'],
            strict: true,
            allowInvalid: false,
        },
        { data: 'destination',
            type: 'dropdown',
            source: ['Chip - C', 'Landfill - L', 'Sort - S', 'Process - P'],
            strict: true,
            allowInvalid: false,
        },
        { data: 'comment' },
    ],
};
const pendingEdits = ref([]);
watch(
    () => props.tData,
    (newVal) => {
        if (newVal == null) {
            //newVal = (props.selector == 'viewEntry') ? tableEntriesSettings.schema : tableSortingSettings.schema;
            newVal = tableEntriesSettings.schema;
        }
        tableData.value = newVal;
        settings.value = tableSettings();
        const ht = instance.refs.hotTableComponent.hotInstance;
        ht.updateSettings(settings.value);
    },
);
watch(
    () => props.print,
    () => {
        printReport();
    },
);
watch(
    () => props.download,
    () => {
        downloadCSV();
    },
);
watch(
    () => props.save,
    () => {
        SaveEdits();
    },
);

const customDateRendererGate = function (instance, td, row, col, prop, value, cellProperties,) {
    if(value) {
        if(value.includes('-')){
            const [year, month, day] = value.split('-');
            value = month + '/' + day + '/' + year;
        }
    }
    Handsontable.renderers.TextRenderer(instance, td, row, col, prop, value, cellProperties,);
    return td;
};

Handsontable.renderers.registerRenderer('customDateRendererGate', customDateRendererGate,);

function tableSettings() {
    let columnHeaders = [];
    let columnNames = [];
    let avgText = 'Units Average: ';
    if (props.selector === 'viewEntry') {
        columnHeaders = tableEntriesSettings.columnHeaders;
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
            callback: function (key) {
                if (key == 'RemoveRow' && user.perms.admin) {
                    const hot = instance.refs.hotTableComponent.hotInstance;
                    const selected = hot.getSelected()[0][0];
                    const rowData = hot.getSourceDataAtRow(selected);
                    for (let i = 0; i < tableData.value.length - 1; i++) {
                        if (tableData.value[i]['id'] === rowData['id']) {
                            tableData.value.slice(i, i);
                            break;
                        }
                    }
                    rowData.changes = ['removeRow'];
                    pendingEdits.value.push(rowData);
                    hot.alter('remove_row', selected);
                    emit('pendingEdits', true);
                    toasty({ mode: 'success', message: 'Save Edits to permanently remove' });
                }
            },
            items: {
                RemoveRow: {
                    name: 'Remove Row', // Set custom text for predefined option
                },
            },
        },
        afterSelection: function (row, col, row2, col2) {
            if (row == -1) return;
            const data = [];
            const hot = instance.refs.hotTableComponent.hotInstance;
            /* istanbul ignore start */

            // if selection is inverted, revert
            if (row > row2) {
                const v = row;
                row = row2;
                row2 = v;
            }
            if (col != col2) {
                pageData.avg = '';
                pageData.sum = 'Must select single column';
                return;
            }
            let colName = 'Units Sum: ';
            if (props.selector == 'viewEntry') {
                avgText = ' Average: ';
                //push data into array
                if (col != 4 && col != 5 && col != 6) {
                    col = 1;
                }
                switch (col) {
                    case 1:
                        colName = 'Units Sum: ';
                        break;
                    case 4:
                        colName = 'Length Sum: ';
                        break;
                    case 5:
                        colName = 'Width Sum: ';
                        break;
                    case 6:
                        colName = 'Height Sum: ';
                        break;
                }
            } else if (col != 1) {
                col = 1;
            }
            for (let i = row; i <= row2; ++i) {
                data.push(hot.getDataAtCell(i, col));
            }
            const result = data.map(function (x) {
                return parseFloat(x);
            });
            if (isNaN(result[0])) return;
            // Sum the selection
            let tempSum = result.reduce((a, b) => a + b, 0);
            // reduce to 1 decimal place
            tempSum = colName + Math.round(tempSum * 10) / 10;
            pageData.sum = tempSum;
            // Average the selection
            let total = 0;
            for (let i = 0; i < result.length; ++i) {
                total += result[i];
            }
            let tempAvg = total / result.length;
            // reduce to 1 decimal place
            tempAvg = avgText + Math.round(tempAvg * 10) / 10;
            pageData.avg = tempAvg;
        },
        afterChange: function (changes, source) {
            if (source === 'edit') {
                const ht = instance.refs.hotTableComponent.hotInstance;
                for (let i = 0; i < changes.length; i++) {
                    const allData = ht.getSourceDataAtRow(changes[i][0]);
                    const col = changes[i][1]
                    const row = changes[i][0];
                    let message = validateChanges(changes[0][2], changes[0][3], col);
                    if(allData['id'] == undefined) {
                        message = 'cannot edit new row';
                        ht.alter('remove_row', row);
                        toasty({ mode: 'warning', message: message});
                        return;
                    }
                    if(message === -1) {
                        return;
                    }

                    else if (message) {
                        ht.setDataAtRowProp(row, col, changes[0][2]);
                        toasty({ mode: 'warning', message: message});
                        return;
                    }
                    allData.changes = changes;
                    pendingEdits.value.push(allData);
                }
                emit('pendingEdits', true);
            }
        },
    };
}
function validateChanges(oldVal, newVal, columnName) {
    if(oldVal == newVal) {
        return -1;
    }
    if(columnName == 'units' || columnName == 'length' || columnName == 'width' || columnName == 'height') {
        const valNumber = Number(newVal);
        if(!isFinite(valNumber) || newVal === ''){
            return columnName + ' must be a number';
        }
        if(newVal >= 1000) {
            return columnName + ' must be less than 1000';
        }
        if(newVal < 0) {
            return columnName + ' must be a positive number';
        }
    }
    if(columnName != 'bin' && newVal === '') {
        return columnName + ' cannot be empty';
    }
    if(columnName == 'date' && newVal !== '') {
        const date = new Date(newVal);
        if(isNaN(date.getTime())) {
            return columnName + ' must be a valid date';
        }
    }
}
function SaveEdits() {
    const url =
        props.selector == 'viewEntry'
            ? '/saveEntriesEdits'
            : '/saveSortingEdits';
    post_to_server(
        {
            headers: {
                Authorization: 'Bearer ' + localStorage.getItem('token'),
            },
            url: url,
            method: 'POST',
            data: pendingEdits.value,
            success: function (data) {
                if (!data.result) {
                    pendingEdits.value = [];
                    //emit('pendingEdits', false);
                    toasty({ mode: 'success', message: 'Saved Edits' });
                }
            },
            error: function () {
                toasty({ mode: 'warning', message: "Couldn't Save Edits" });
            },
            complete: function () {},
        },
        offlineStore,
    );
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

function filterTableData() {
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
    const ht = instance.refs.hotTableComponent.hotInstance;
    ht.updateSettings(settings.value);
    filterTableData();
});
</script>

<template>
    <hot-table
        ref="hotTableComponent"
        class="data-sheet m-3"
        :settings="settings"
        :data="tableData"
        :instance="instance"
    ></hot-table>
</template>

<style>

:root {
    --background: rgb(54, 61, 71);
    --color: rgb(224, 224, 224);
    --color2: rgb(123, 123, 123);
    --table-border-color: #444;
    --table-header-background: rgb(54, 61, 71);
    --table-header-color: #FFF;
    --background-color-row-odd: rgb(54, 61, 71);
    --background-color-row-even: rgb(54, 61, 71);
    --background-color-row-first: rgb(54, 61, 71);
    --cell-color: rgb(21, 24, 28);
    --cell-selected-color: #222;
    --ctx-background: #e0e0e0;
    --ctx-background-hover: rgb(186, 198, 215);
    --border-left: 1px solidrgb(146, 157, 172)  --scroll-track-dark: #111;
    --scroll-thumb-dark: #575757;
    --scroll-thumb-hover-dark: #575757;
    --scroll-track: #7b7fa4;
    --scroll-thumb: rgb(96 165 250);
    --scroll-thumb-hover: rgb(156, 201, 255);
}

.dark .data-sheet {
    border-top: thin solid var(--table-border-color);
    border-bottom: thin solid var(--table-border-color);
}

.dark .data-sheet .handsontable .htDimmed2 {
    color: var(--color2);
}
.data-sheet .handsontable .htDimmed2 {
    color: var(--color2);
}
.dark .data-sheet .handsontable .htDimmed {
    color: var(--color);
}

.dark .data-sheet .handsontable {
    color: var(--color);
}

.data-sheet .handsontable .htDimmed {
    color: black;
}

.dark .data-sheet .handsontable .wtHolder {
    background-color: var(--table-header-background);
}

/* All headers */
.dark .data-sheet .handsontable th {
    background-color: var(--table-header-background);
    color: var(--table-header-color);
    border-left: var(--border-left);
}

/* Row headers */
.dark .data-sheet .ht_clone_left th {
    background-color: var(--table-header-background);
    color: var(--table-header-color);
}

/* Column headers */
.dark .data-sheet .ht_clone_top th {
    background-color: var(--table-header-background);
    color: var(--table-header-color);
}

/* Row headers */
.dark .data-sheet .ht_clone_top_left_corner th {
    border-bottom: 1px solid var(--table-border-color);
}

.dark .data-sheet .ht_clone_left th {
    border-right: 1px solid var(--table-border-color);
    border-left: 1px solid var(--table-border-color);
}

/* Column headers */
.dark .data-sheet .ht_clone_top th {
    border-top: 1px solid var(--table-border-color);
    border-right: 1px solid var(--table-border-color);
    border-bottom: 1px solid var(--table-border-color);
}

.dark .data-sheet .ht_clone_top_left_corner th {
    border-right: 1px solid var(--table-border-color);
}

.dark .data-sheet .handsontable .changeType {
    background: inherit;
    border-color: var(--table-border-color);
}

/* Borders */
.dark .data-sheet .handsontable th,
.dark .data-sheet .handsontable td {
    border-right: 1px solid var(--table-border-color);
    border-bottom: 1px solid var(--table-border-color);
}

.dark .data-sheet .handsontable tr:first-child td,
.dark .data-sheet .handsontable tr:first-child th {
    border-top: 1px solid var(--table-border-color);
}

.dark .data-sheet .ht_master tr>td {
    border-bottom: 1px solid var(--table-border-color);
}

/* Right */
.dark .data-sheet .ht_master tr>td {
    border-right: 1px solid var(--table-border-color);
}

.dark .data-sheet .dark .data-sheet .handsontable .htNoFrame+td,
.dark .data-sheet .handsontable .htNoFrame+th,
.dark .data-sheet .handsontable.htRowHeaders thead tr th:nth-child(2),
.dark .data-sheet .handsontable td:first-of-type,
.dark .data-sheet .handsontable th:first-child,
.dark .data-sheet .handsontable th:nth-child(2) {
    border-left: 1px solid var(--table-border-color);
}

.dark .data-sheet .ht_clone_top_left_corner thead tr th:nth-last-child(2) {
    border-right: 1px solid var(--table-border-color);
}

.dark .data-sheet .handsontable th:last-child {
    border-right: 1px solid var(--table-border-color);
    border-bottom: 1px solid var(--table-border-color);
}

/* Selected cell */
.dark .data-sheet tr>td {
    background-color: var(--cell-color);
}

/* Selected cell */
.dark .data-sheet tr>td.current {
    background-color: var(--cell-selected-color);
}

/* Context menu */
.htContextMenu tr,
.htDropdownMenu tr,
.htFiltersConditionsMenu tr {
    background-color: var(--ctx-background);
}


.htContextMenu table tbody tr td,
.htDropdownMenu table tbody tr td,
.htFiltersConditionsMenu table tbody tr td {
    background-color: var(--ctx-background);
}

.htContextMenu table tbody tr td.current,
.htContextMenu table tbody tr td.zeroclipboard-is-hover,
.htDropdownMenu table tbody tr td.current,
.htDropdownMenu table tbody tr td.zeroclipboard-is-hover,
.htFiltersConditionsMenu table tbody tr td.current,
.htFiltersConditionsMenu table tbody tr td.zeroclipboard-is-hover {
    background-color: var(--ctx-background-hover);
}


.htContextMenu .handsontable table td.htCustomMenuRenderer,
.htDropdownMenu .handsontable table td.htCustomMenuRenderer {
    background-color: var(--ctx-background);
}

.handsontable .htUISelectCaption,
.handsontable .htUISelectCaption:hover {
    background-color: var(--ctx-background);
}

/* Scroll bar */
::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

::-webkit-scrollbar-track {
    background: var(--scroll-track);
}

::-webkit-scrollbar-thumb {
    background: var(--scroll-thumb);
}

::-webkit-scrollbar-thumb:hover {
    background: var(--scroll-thumb-hover);
}

.hot-display-license-info {
    opacity: 0 !important;
}
</style>
