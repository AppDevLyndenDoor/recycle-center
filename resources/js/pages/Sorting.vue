<script setup>

import axios from 'axios';
import { onMounted, reactive } from 'vue';
import ProductButtons from '@/components/ProductButtons.vue';
import { post_to_server } from '@/majax.js';
import {useOfflineStore} from '@/store/useOfflineStore.js';
import {useToastyStore} from '@/store/useToastyStore.js';
import {useUserStore} from '@/store/useUserStore';

const toastySettings = useToastyStore();
const user = useUserStore();
const offlineStore = useOfflineStore();

const state = reactive({
    sortingModel: {
        user: 'Select User',
        units: '',
        product: '',
        date: 0,
        picked_timestamp: 0,
        company: 'Victory Millwork',
    },
    productSortingSpecModels: [],
    companies: [
        'Lynden Door',
        'Victory Millwork',
        'LD Trucking',
    ],
    unitSortingCache: [],
})
function keymonitor(event){
    if (event.key === 'Enter') {
        event.currentTarget.blur();
    }
}
function clickedCompanyButtons(index) {

    state.sortingModel.company = state.companies[index];
}
function getSortingProducts(){
    axios({
        method: 'GET',
        url: '/pickupSortingProduct',
        headers: {
            "Authorization": "Bearer " + localStorage.getItem('token'),
        }
    }) .then((response) => {
        if (response.data.length >= 0) {

            state.productSortingSpecModels = response.data;
            //state.sortingModel.product = response.data[0].name;
            localStorage.setItem('database_sortingProducts', JSON.stringify(response.data));
        }
    }, (error) => {
        if (error.message != undefined) {
            toasty({ mode: 'error', response: error, request: error.request, message: error.message });
        }
    });
}
function productButton(product){

    state.sortingModel.product = product.name;

}
function getRandomInt(max){
    return Math.floor(Math.random() * max)+1;
}
function sortingValidation(){

    if (user.userName == 'Recycle Center' || user.userName == 'Select User' || user.userName == 'Op Erator') {
        return 'Please Select a User Name'
    }
    if (!state.sortingModel.units) {
        return 'Quantity required'
    }
    return '';
}
function SubmitSortingEntry() {
    const errorMessage = sortingValidation();
    if (!errorMessage) {
        state.sortingModel.picked_timestamp = Date.now();
        if (!state.unitSortingCache) {
            state.unitSortingCache = [];
            JSON.parse(localStorage.setItem('databaseSorting', '[]'));
        }
        state.unitSortingCache.push(state.sortingModel);
        localStorage.setItem('database', JSON.stringify(state.unitSortingCache));
        const obj = {};
        Object.assign(obj, state.sortingModel);
        obj.idempotency = getRandomInt(999999999);
        post_to_server({
            headers: {
                "Authorization": "Bearer " + localStorage.getItem('token'),
            },
            url: '/pickupSortingProduct',
            method: 'POST',
            data: obj,
            success: function(data) {
                if (!data.result) {

                    toasty({ mode: 'success', message: 'Entry Submitted' });
                }
            },
            error: function() {
                toasty({ mode: 'warning', message: "couldn't upload" });
            },
            complete: function() {
            },
        }, offlineStore);
        state.picked_timestamp = 0;
        state.sortingModel.units = '';
    }

}
function CurrentDate() {
    const today = new Date();
    const dd = String(today.getDate()).padStart(2, '0');
    const mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
    const yyyy = today.getFullYear();
    return mm + '/' + dd + '/' + yyyy;
}
onMounted(() => {
    state.sortingModel.user = user.userName;
    state.sortingModel.company = 'Victory Millwork';
    state.sortingModel.date = CurrentDate()
    getSortingProducts();
})
function toasty({ mode, request, response, message }) {
    // Setting up toast notification with given parameters and a 5-second delay
    toastySettings.mode = mode;
    toastySettings.request = request;
    toastySettings.response = response;
    toastySettings.message = message;
    toastySettings.visible = true;
}

</script>

<template>
    <div hidden id="TemplateSortingEntry">

        <div class="row">
            <div class="col">
                <h2 id="titleCompany">Company</h2>
            </div>
        </div>

        <div  class="flex flex-wrap w-screen centered place-content-center">
            <div class="flex-box centered" v-for="(company, index) in state.companies" :key="index">
                <ProductButtons :id="'companyButtons-'+index" @clicked="clickedCompanyButtons(index)" :active="state.sortingModel.company === company">{{company}}</ProductButtons>
            </div>
        </div>
        <hr>

        <div class="row">
            <div class="col">
                <h2 id="titleProducts">Products</h2>
            </div>
        </div>

        <div  class="flex flex-wrap w-screen centered place-content-center">
            <div v-for="(product, index) in state.productSortingSpecModels" :key="index">
                <ProductButtons :id="'productButtons-'+index" :disabled="false" :active="product.name === state.sortingModel.product"
                                @clicked="productButton(product, index)">{{product.name}}</ProductButtons>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col">
                <h2 id="titleSortingProducts">Units</h2>
            </div>
        </div>

        <div class="flex justify-content-center centered">
            <div class="col centered justify-content-center">
                <input v-on:keyup="keymonitor"  onfocus="this.value=''" type="number" id="sortingUnits" v-model="state.sortingModel.units" min="0" class="centered">
            </div>
        </div>
        <hr>



        <div class="grid grid-cols-9 justify-content-between">
            <div class="col-span-3 grid grid-cols-subgrid details">
                <label>Date</label>
                <input type="text" id="DateSortingPage" class="inputDetails date" maxlength="60" v-model="state.sortingModel.date"
                       min="1970-01-01" disabled>
            </div>

            <div class="col-span-3 grid grid-cols-subgrid details">
                <label>Company</label>
                <input disabled type="text" id="SortingCompany" class="inputDetails" maxlength="60" v-model="state.sortingModel.company"
                       autocapitalize="off" autocomplete="off" spellcheck="false" autocorrect="off">
            </div>
            <div class="col-span-3 grid grid-cols-subgrid details">
                <label>Product</label>
                <input disabled type="text" id="sortingProduct" class="inputDetails" maxlength="60" v-model="state.sortingModel.product"
                       autocapitalize="off" autocomplete="off" spellcheck="false" autocorrect="off">
            </div>
        </div>

        <div class="grid grid-cols-9 justify-content-between">
            <div class="col-span-3 grid grid-cols-subgrid details">
                <label>Units</label>
                <input disabled onfocus="this.value=''" type="number" id="sortingQuantity" class="inputDetails" maxlength="100"
                       v-model="state.sortingModel.units">
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col centered">

                <button id="sortingSubmit" type="button" class="btn btn-primary submit centered lrgBtn"
                        @click="SubmitSortingEntry()">SUBMIT</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
input{
    font-size:28px;
    width: 64px;
    border-width:2px;
    border-color: black;
}
.modal-body input{
    width:400px;
}
.inputDetails {
    width: 214px;
}
</style>
