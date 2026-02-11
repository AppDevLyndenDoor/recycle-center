<script setup>
import axios from 'axios';
import { onMounted, reactive, watch } from 'vue';
import Dialog from '@/components/Dialog.vue';
import GenericButton from '@/components/GenericButton.vue';
import ProductButtons from '@/components/ProductButtons.vue';
import {useToastyStore} from '@/store/useToastyStore.js';
import {useUserStore} from '@/store/useUserStore.js';

const toastySettings = useToastyStore();
const state = reactive({
    userNamesText: '',
    productSpecModels: [],
    productSortingSpecModels: [],
    binModels: [],
    companyArray: [],
    showEditDialog: false,
    entryModel: {
        user: 'test user',
        units: '',
        uom: '',
        product: '',
        length: 0,
        width: 0,
        height: 0,
        bin: '',
        date: 0,
        picked_timestamp: 0,
        company: 'Lynden Door',   //modified
        destination: 'Chip - C',
        comment: '',
    },
    sortingModel: {
        user: 'Select User',
        units: '',
        product: '',
        date: 0,
        picked_timestamp: 0,
        company: '',
    },
    createProduct: {
        name: '',
        company: '',
        uom: 'each',
        id: -1,
    },
    //selectedCompanies: [true,false,false, false], // Lynden Door, VM, LDT, All
    createSortingProduct: {
        name: '',
        id: -1,
    },
    createBin: {
        binNumber: '',
        yards: 0,
        location: '',
        company: '',
    },
    createItem: {},
    companies: [
        'Lynden Door',
        'Victory Millwork',
        'LD Trucking',
    ],
    deleteProductDialog: false,
})
const user = useUserStore();

function productButton(product){
    debugger;
    state.createItem = {
        edit: {
            name: product.name,
            uom: product.uom,
        },
        companyArray: product.company.split(','),
        company: '',
        model: 'Product',
        id: product.id,
        active: [false,false,false]
    };
    for (let i = 0; i < state.createItem.companyArray.length; i++) {
        const company = state.createItem.companyArray[i];
        const index = state.companies.indexOf(company);
        state.createItem.active[index] = true;
    }
    state.showEditDialog = true;
}
function sortingProductButton(product){
    debugger;
    state.createItem = {
        edit: {
            name: product.name,
        },
        model: 'Sorting Product',
        id: product.id,
    };
    state.showEditDialog = true;

}

function binButton(bin){
    debugger
    state.createItem = {
        edit: {
            binNumber: bin.binNumber,
            yards: bin.yards,
            location: bin.location,
        },
        company: bin.company,
        model: 'Bin',
        id: bin.id,
    };
    state.showEditDialog = true;
}
function saveEdit(product) {
    debugger;
    if (product.edit.name != '') {
        state.createProduct.company = product.companyArray.toString().trim();
        state.createProduct.name = product.edit.name.trim();
        state.createProduct.uom = product.edit.uom.trim();
        state.createProduct.id = product.id;
        axios({
            method: 'POST',
            url: '/saveProduct',
            data: state.createProduct,
            headers: {
                "Authorization": "Bearer " + localStorage.getItem('token'),
            }
        }).then(() => {

            toasty({ mode: 'success', message: 'Saved Edit' });

        }, (error) => {
            if (error.message != undefined) {
                toasty({ mode: 'error', response: error, request: error.request, message: error.message });
            }
        });
    }
}
function saveSorting(product) {
    if (product.edit.name != '') {
        state.createSortingProduct.name = product.edit.name.trim();
        state.createSortingProduct.id = product.id;
        axios({
            method: 'POST',
            url: '/saveSortingProduct',
            data: state.createSortingProduct,
            headers: {
                "Authorization": "Bearer " + localStorage.getItem('token'),
            }
        }).then(() => {
        }, (error) => {
            if (error.message != undefined) {
                toasty({ mode: 'error', response: error, request: error.request, message: error.message });
            }
        });
    }
}

function saveBin(bin) {
    if(bin.edit.binNumber == ''){
        return;
    }
    state.createBin.company = bin.company.trim();
    state.createBin.yards =  bin.edit.yards;
    state.createBin.location = bin.edit.location.trim();
    state.createBin.id = bin.id;
    axios({
        method: 'POST',
        url: '/saveBin',
        data: bin,
        headers: {
            "Authorization": "Bearer " + localStorage.getItem('token'),
        }
    }).then(() => {
        toasty({ mode: 'success', message: 'successfully saved Bin' });
    }, (error) => {
            if (error.message != undefined) {
                toasty({ mode: 'error', response: error, request: error.request, message: error.message });
            }
        }
    )
}
function saveUsers() {
    if(state.userNamesText == ''){
        return;
    }
    let usersNames = state.userNamesText;
    // Repeats regex magic until there's nothing left to change.
    while (usersNames.match(/^,|,$|^\s{1,}|\s{1,}$|,{2,}|( , | ,|, )| {2,}/g) != null) {
        usersNames = usersNames.replace(/,{2,}/g, ',');
        usersNames = usersNames.replace(/ , | ,|, /g, ',');
        usersNames = usersNames.replace(/^,|,$|^\s{1,}|\s{1,}$/g, '');
        usersNames = usersNames.replace(/ {2,}/g, ' ');
        usersNames = usersNames.trim();
    }
    axios({
        method: 'POST',
        url: '/saveUserNames',
        data: [usersNames],
        headers: {
            "Authorization": "Bearer " + localStorage.getItem('token'),
        }
    }).then(() => {
        user.userNameList = state.userNamesText.split(',');
        user.userNameList.unshift('Select User');
        toasty({ mode: 'success', message: 'successfully saved Users' });
    }, (error) => {
        if (error.message != undefined) {
            toasty({ mode: 'error', response: error, request: error.request, message: error.message });
        }
    })
}

function getPickupProduct(){
    axios({
        method: 'GET',
        url: '/pickupProduct',
        headers: {
            "Authorization": "Bearer " + localStorage.getItem('token'),
        }
    }) .then((response) => {
        if (response.data.length >= 0) {
            state.productSpecModels = response.data;

            localStorage.setItem('database_products', JSON.stringify(response.data));

        }
    }, (error) => {
        if (error.message != undefined) {
            toasty({ mode: 'error', response: error, request: error.request, message: error.message });
        }
    });
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
function getPickupBins(){
    axios({
        method: 'GET',
        url: '/pickupBin',
        headers: {
            "Authorization": "Bearer " + localStorage.getItem('token'),
        }
    }) .then((response) => {
        if (response.data.length > 0) {
            state.binModels = response.data;
            localStorage.setItem('database_bins', JSON.stringify(response.data));

        }
    }, (error) => {
        if (error.message != undefined) {
            toasty({ mode: 'error', response: error, request: error.request, message: error.message });
        }
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
function newProduct(){
    state.createItem = {
        edit: {
            name: '',
            uom: '',
        },
        companyArray: [],
        model: 'Product',
    }
    state.showEditDialog = true;
}
function newSortingProduct(){
    state.createItem = {
        edit: {
            name: '',
        },
        company: [],
        model: 'Sorting Product',
    }
    state.showEditDialog = true;
}
function newBin(){
    state.createItem = {
        edit: {
            binNumber: '',
            yards: 0,
            location: '',
        },
        company: [],
        model: 'Bin',
    }
    state.showEditDialog = true;
}
function clickedCompanyButton(company, model, index) {
    debugger;
    if (model === 'product') {
        const companyIndex = state.createItem.companyArray.indexOf(company);
        if (companyIndex < 0) {
            state.companyArray.push(company);
            state.createItem.active[index] = true;
        } else {
            state.companyArray.splice(companyIndex, 1);
            state.createItem.active[index] = false;
        }
    }
    else{
        state.createItem.company = company;
    }
}
function save(item){
    debugger;
    if (item.model === 'Product') {
        saveEdit(item);
    } else if (item.model === 'Sorting Product') {
        saveSorting(item);
    } else if (item.model === 'Bin') {
        saveBin(item);
    }
}
function deleteProduct(product){

    let url,id,name;
    if(product.model === 'Product'){
       url = '/deleteProduct';
       id = state.productSpecModels[product.id]['id'];
       name = state.productSpecModels[product.id]['name'];
    }
    else if(product.model === 'Bin') {
        url = '/deleteBin';
        id = state.binModels[product.id]['id'];
        name = state.binModels[product.id]['binNumber'];
    }
    else{
        url = '/deleteSortingProduct';
        id = state.productSortingSpecModels[product.id]['id'];
        name = state.productSortingSpecModels[product.id]['name'];
    }
    debugger;
    axios({
        method: 'POST',
        url: url,
        data: {id: id,
            name: name},
        headers: {
            "Authorization": "Bearer " + localStorage.getItem('token'),
        }
    })

    state.showEditDialog = false;
    state.deleteProductDialog = false;

}
watch( () => user.userNameList, (newVal) => {
    //debugger
    state.userNamesText = newVal;
    state.userNamesText.splice('Select User,', 1);
    state.userNamesText = state.userNamesText.join(',');
})

onMounted(() => {
        state.userNamesText = user.userNameList;
        state.userNamesText.splice('Select User,', 1);
        state.userNamesText = state.userNamesText.join(',');
        getPickupProduct();
        getSortingProducts();
        getPickupBins();
})
</script>

<template>
    <Dialog v-if="state.showEditDialog" :size="'md'" :dialogVisible="state.showEditDialog"
            :title="state.createItem.model" class="fixed inset-0 z-50">
        <div v-if="state.createItem.model === 'Bin'" class="flex flex-wrap centered">
            <div v-for="(company, index) in state.companies" :key="index">
                <ProductButtons @clicked="clickedCompanyButton(company,state.createItem.model)" :active="state.createItem.company === company">{{company}}
                </ProductButtons>
            </div>
        </div>
        <div v-if="state.createItem.model === 'Product'" class="flex flex-wrap centered">
            <div v-for="(company, index) in state.companies" :key="index">
                <ProductButtons @clicked="clickedCompanyButton(company,state.createItem.model,index)" :active="state.createItem.active[index]">{{company}}
                </ProductButtons>
            </div>
        </div>

            <div v-for="(value, index) in Object.keys(state.createItem.edit)" :key="index">
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <label class="text-md px-4">{{value}}:</label>
                    <input v-model="state.createItem.edit[value]" class="inputDetails  mx-2" :placeholder="value">
                </div>
            </div>
        <div>
            <button class="btn btn-primary" :class="[
                        'absolute',
                        'bottom-2',
                        'left-0',
                        'h-8',
                        'w-32',
                        'inline-flex',
                        'm-4',
                        'my-[2px]',
                        'py-[3px]']" @click="state.showEditDialog = false">
                <p class="text-md centered w-full relative whitespace-nowrap">Cancel</p>
            </button>
        </div>
        <div class="flex justify-center">
            <button class="btn btn-primary" :class="[
                    'absolute',
                    'bottom-2',
                    'h-8',
                    'w-32',
                     'inline-flex',
                     'm-4',
                     'my-[2px]',
                     'py-[3px]']" @click="state.deleteProductDialog = true">
                <p class="text-md centered w-full relative whitespace-nowrap">Delete</p>
            </button>
        </div>
        <div>
            <button class="btn btn-primary" :class="[
                    'absolute',
                    'bottom-2',
                    'right-0',
                    'h-8',
                    'inline-flex',
                    'w-32',
                    'm-4',
                    'my-[2px]',
                    'py-[3px]',
                    ]" @click="save(state.createItem)">
                <p class="text-md centered w-full relative whitespace-nowrap">save</p>
            </button>
        </div>
    </dialog>
    <Dialog v-if="state.deleteProductDialog" :size="'sm'" :dialogVisible="state.deleteProductDialog" :title="'Delete Labor Code'"
            class="fixed inset-0 z-50">
        <p class="text-xl text-zinc-700 mt-1 ml-3 mr-4 dark:text-gray-200 whitespace-nowrap text-center">Are you
            sure you want to delete this labor code?</p>
        <div class="">
            <GenericButton :class="[
                    'absolute',
                    'bottom-2',
                    'left-0',
                    'h-8',
                    'w-32',
                    'inline-flex',
                    'm-4',
                    'my-[2px]',
                    'py-[3px]',
                ]" @click="state.deleteProductDialog = false">
                <p class="text-md text-center w-full relative top-[2px] whitespace-nowrap">Cancel</p>
            </GenericButton>
            <GenericButton :class="[
                    'absolute',
                    'bottom-2',
                    'right-0',
                    'h-8',
                    'inline-flex',
                    'w-32',
                    'm-4',
                    'my-[2px]',
                    'py-[3px]',
                ]" @click="deleteProduct()">
                <p class="text-md text-center w-full relative top-[2px] whitespace-nowrap">Delete</p>
            </GenericButton>
        </div>
    </Dialog>

    <div id="TemplateSettings">
        <div>
            <div class="row justify-content-center">
                <div class="col centered">
                    <button id='CreateProduct' type="button" class="btn btn-primary selBtn center" @click="newProduct()">Create New Product</button>
                    <button id="CreateBin" type="button" class="btn btn-primary selBtn center" @click="newBin()">Create New Bin</button>
                    <button id="CreateSorting" type="button" class="btn btn-primary selBtn center" @click="newSortingProduct()">Create New Sorting</button>
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col">
                    <h2 id="titleProducts">Products</h2>
                </div>
            </div>

            <div  class="flex flex-wrap w-screen centered place-content-center">
                <div v-for="(product, index) in state.productSpecModels" :key="index">
                    <ProductButtons :product="product" :index="index" :disabled="false" :active="product.name === state.entryModel.product"
                                    @clicked="productButton(product, index)">{{product.name}}</ProductButtons>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col">
                    <h2 id="titleProducts">Sorting Products</h2>
                </div>
            </div>

            <div  class="flex flex-wrap w-screen centered place-content-center">
                <div v-for="(product, index) in state.productSortingSpecModels" :key="index">
                    <ProductButtons :disabled="false" :active="product.name === state.sortingModel.product"
                                    @clicked="sortingProductButton(product)">{{product.name}}</ProductButtons>
                </div>
            </div>

            <hr>


            <div class="row justify-content-center">
                <div class="col-4">
                    <h2 id="titleProducts">Bin Numbers</h2>
                </div>
            </div>
            <div  class="flex flex-wrap w-screen centered place-content-center">
                <div v-for="bin in state.binModels" :key="bin.binNumber">
                    <ProductButtons @clicked="binButton(bin)">{{bin.binNumber}}</ProductButtons>
                </div>
            </div>
            <hr>
            <div class="row justify-content-center">
                <div class="col-4">
                    <h2 id="titleProducts">Operators</h2>
                </div>
            </div>
            <div class="row justify-content-center centered">
                <div class="col-5">
                    <textarea id="operatorsTextarea" rows="4" cols="52" v-model="state.userNamesText"
                              class="border-black border-2 ml-1"
                              autocapitalize="off"
                              autocomplete="off"
                              spellcheck="false">
                    </textarea>
                </div>

            </div>
            <div class="row justify-content-center centered">
                <button id="saveUserButton" type="button" class="btn btn-primary selBtn center col-2 " @click="saveUsers()">Save User List</button>
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
