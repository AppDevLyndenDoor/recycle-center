<script setup>
import '../../css/app.css';
import '../../css/style.css';
import axios from 'axios';
import { onMounted, reactive, watch } from 'vue';
import Dialog from '@/components/Dialog.vue';
import ProductButtons from '@/components/ProductButtons.vue';
import { post_to_server } from '@/majax.js';
import {useOfflineStore} from '@/store/useOfflineStore.js';
import {useToastyStore} from '@/store/useToastyStore.js';
import { useUserStore } from '@/store/useUserStore.js';


defineProps(['offline'])
defineEmits(['offlinePost']);
const user = useUserStore();
const toastySettings = useToastyStore();
const offlineStore = useOfflineStore();
const state = reactive( {
    showBinDialog: false,
    editComment: false,
    newComment: '',
    // Entry data structure as it is reflected in the database
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

    binModels: [],
    // User object contains user specific settings
    user: {
        name: 'test',
        pseudonym: 'user',
        perms: {
            admin: false,
            operator: true
        },
    },

    productSpecModels: [],

    unitCache: [],
    companies: [
        'Lynden Door',
        'Victory Millwork',
        'LD Trucking',
    ],
    destinations: [
        'Chip - C',
        'Landfill - L',
        'Sort - S',
        'Process - P',
    ],
    destination: 'Chip - C',
    uoms: [
        'each',
        'yards',
        'gallons'
    ],
    specModding: '',
    mode: 'each',
    imageList: [],
    serverImageList: {},
    uploadResults: 0,
    uploadQuantity: 0,
    imageDeleteList: [],

/*    watch: {
        'OfflinePosts'(val) {
            localStorage.setItem('OfflinePosts', JSON.stringify(val))
        },

    }*/
});

function CurrentDate() {
    const today = new Date();
    const dd = String(today.getDate()).padStart(2, '0');
    const mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
    const yyyy = today.getFullYear();
    return mm + '/' + dd + '/' + yyyy;
}

function toasty({ mode, request, response, message }) {
    // Setting up toast notification with given parameters and a 5-second delay
    toastySettings.mode = mode;
    toastySettings.request = request;
    toastySettings.response = response;
    toastySettings.message = message;
    toastySettings.visible = true;
}

function keymonitor(event){
    if (event.key === 'Enter') {
        event.currentTarget.blur();
    }
}
function calcUnits(){
    state.entryModel.width = parseFloat(state.entryModel.width) || 0;
    state.entryModel.length = parseFloat(state.entryModel.length) || 0;
    state.entryModel.height = parseFloat(state.entryModel.height) || 0;
    state.entryModel.units = Math.round(state.entryModel.width * state.entryModel.length * state.entryModel.height / 1728 / 27 * 10000) / 10000;
}



function pickBin(bin){
    if(bin.company == state.entryModel.company){
        state.entryModel.bin = bin.binNumber;
        state.entryModel.company = bin.company;
        state.entryModel.units = Math.abs(bin.yards) || 0;
        state.entryModel.uom = 'yards';
    }

    state.showBinDialog = false
}


function destinationClicked(destination){
    state.destination = destination;
    state.entryModel.destination = destination;
}

function clickedCompanyButtons(model, index) {

    state[model].company = state.companies[index];
    if (model == 'entryModel') {
        state[model].company = state.companies[index];
        for(const product of state.productSpecModels) {
            const companies = product.company.split(',');
            product.disabled = ((companies.indexOf(state.entryModel.company) < 0 ) && product.company !== 'All')
        }
    }
}

function productButton(product){
    debugger;
    state.entryModel.product = product.name;
    state.entryModel.uom = product.uom;
    state.mode = product.uom;
    if(product.uom != 'bin'){
        state.entryModel.bin = '';
        state.entryModel.units = 0;
    }
}
function getRandomInt(max){
    return Math.floor(Math.random() * max)+1;
}
function enteryValidation(){
    debugger
    if (state.entryModel.user == 'Recycle Center' || state.entryModel.user == 'Select User' || state.entryModel.user == 'Op Erator') {
        return 'Please Select a User Name'
    }

    if (!state.entryModel.units) {
        return 'Quantity required'
    }
    return '';
}
function SubmitEntry(){
    const error = enteryValidation();
    if(error !== ''){
        toasty({ mode: 'warning', response: error, message: 'Please fill in all required fields'});
        return
    }
    state.entryModel.picked_timestamp = Date.now();
    if(state.unitCache == null){
        state.unitCache = [];
    }
    state.unitCache.push(state.entryModel);
    localStorage.setItem('database', JSON.stringify(state.unitCache));

    const obj = {};
    Object.assign(obj, state.entryModel);
    obj.idempotency = getRandomInt(999999999);

    const post = {
        headers: {
            "Authorization": "Bearer " + localStorage.getItem('token'),
        },
        url: '/pickupUnit',
        method: 'POST',
        data: obj,
        success: function ( )  {
            console.log(200);
            toasty({ mode: 'success', message: 'Entry Submitted' });
        },
        error: function ()  {
            toasty({ mode: 'warning', message: "couldn't upload"});
        },
        complete: function ()  { },
    };
    debugger
    try {
        post.success('test',200); // Test the exact call
    } catch (e) {
        console.error("Error caught:", e);
    }
    post_to_server(post,offlineStore);

    state.entryModel.width = 0;
    state.entryModel.height = 0;
    state.entryModel.bin = '';
    state.entryModel.units = 0;
    state.entryModel.comment = '';
    state.entryModel.picked_timestamp = 0;
    state.entryModel.uom = '';
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
            productButton(state.productSpecModels[0], 0);
            for(const product of state.productSpecModels) {
                const companies = product.company.split(',');
                product.disabled = ((companies.indexOf(state.entryModel.company) < 0 ) && product.company !== 'All')
            }
            localStorage.setItem('database_products', JSON.stringify(response.data));
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


watch( () => user.pseudonym, () => {
    if(user.userName !== undefined){
        state.entryModel.date = CurrentDate()
        if (!user.perms.admin && user.perms.operator) {
            state.entryModel.user = 'Select User';
        } else {
            state.entryModel.user = user.pseudonym;
        }
    }

})

onMounted(() => {
    debugger
    if(user.userName !== undefined) {
        state.entryModel.date = CurrentDate()
    if (!user.perms.admin && user.perms.operator) {
        state.entryModel.user = 'Select User';
    } else {
        state.entryModel.user = user.pseudonym;
    }

    }
    getPickupProduct();
    getPickupBins();
});

</script>

<template>

    <Dialog v-if="state.showBinDialog" :size="'xl'" :dialogVisible="state.showBinDialog"
            :title="'Bin'" class="fixed inset-0 z-50">
        <div class="flex flex-wrap overflow-auto">
            <div class="flex flex-wrap h-[calc(100vh-360px)] w-full overflow-auto">
            <div v-for="bin in state.binModels" :key="bin.binNumber">
                <ProductButtons @clicked="pickBin(bin)">{{bin.binNumber}}</ProductButtons>
            </div>
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
                        'py-[3px]',

                    ]" @click="state.showBinDialog = false">
                <p class="text-md centered w-full relative whitespace-nowrap">Cancel</p>
            </button>
        </div>
    </dialog>
    <Dialog v-if="state.editComment" :size="'md'" :dialogVisible="state.editComment" :title="'Comment'" class="fixed inset-0 z-50">
        <div class="flex flex-wrap centered">
            <textarea id="operatorsTextarea" rows="4" cols="52" v-model="state.newComment"
                      class="border-black border-2 ml-1"
                      autocapitalize="off"
                      autocomplete="off"
                      spellcheck="false"
                      autocorrect="off">
            </textarea>
        </div>
        <div>
            <button class="btn btn-primary" :class="[
                        'absolute',
                        'bottom-2',
                        'right-0',
                        'h-8',
                        'w-32',
                        'inline-flex',
                        'm-4',
                        'my-[2px]',
                        'py-[3px]',
                    ]" @click="state.editComment = false; state.entryModel.comment = state.newComment; state.newComment =''">
                <p class="text-md centered w-full relative whitespace-nowrap">confirm</p>
            </button>
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
                        'py-[3px]',
                    ]" @click="state.editComment = false; state.newComment =''">
                <p class="text-md centered w-full relative whitespace-nowrap">close</p>
            </button>
        </div>
    </Dialog>
        <div id="entyPage">
            <div>
                <div>
                    <div class="justify-content-between no-print">
                        <div id="TemplateEntry">
                            <div class="row">
                                <div class="col">
                                    <h2 id="titleCompany">Company</h2>
                                </div>
                            </div>

                            <div class="centered" >
                                <div class="flex">
                                    <div v-for="(company, index) in state.companies" :key="index">
                                        <ProductButtons @clicked="clickedCompanyButtons('entryModel', index)" :active="state.entryModel.company === company">{{company}}
                                        </ProductButtons>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div>
                                <div>
                                    <h2 id="titleProducts">Products</h2>
                                </div>
                            </div>
                            <div  class="flex flex-wrap w-screen centered place-content-center">
                                <div v-for="(product, index) in state.productSpecModels" :key="index">
                                    <ProductButtons :product="product" :index="index" :disabled="product.disabled" :active="product.name === state.entryModel.product"
                                                    @clicked="productButton(product, index)">{{product.name}}</ProductButtons>
                                </div>
                            </div>

                            <hr>

                            <div>
                                <div>
                                    <h2 id="titleProducts" v-show="(state.mode == 'yards')">Yards</h2>
                                    <h2 id="titleProducts" v-show="(state.mode == 'each')">Each</h2>
                                    <h2 id="titleProducts" v-show="(state.mode == 'gallons')">Gallons</h2>
                                    <h2 id="titleProducts" v-show="(state.mode == 'bin')">Bin#</h2>
                                </div>
                            </div>

                            <div>
                                <div id="TemplateDimensions" class="grid grid-cols-2" v-show="(state.mode == 'yards')">
                                    <div class="grid grid-rows-3 col-span-1 ">
                                        <div class="grid-col-3 place-self-center justify-self-end">
                                            <label class="centered ">Length</label>
                                        </div>
                                        <div class="grid-col-3 place-self-center justify-self-end">
                                            <label class=" centered">Width</label>
                                        </div>
                                        <div class="grid-col-3 place-self-center justify-self-end">
                                            <label class="centered ">Height</label>
                                        </div>
                                    </div>

                                    <div class="grid grid-rows-3 col-span-1 ">
                                        <div class="grid-col-3 place-self-center justify-self-start ml-2">
                                            <input v-on:keyup="keymonitor" @blur="calcUnits()"
                                                   onfocus="this.value=''" type="number" id="Length" v-model="state.entryModel.length" min="0"
                                                   class="numberInput centered">
                                        </div>
                                        <div class="grid-col-3  place-self-center justify-self-start ml-2">
                                            <input v-on:keyup="keymonitor" @blur="calcUnits()"
                                                   onfocus="this.value=''" type="number" id="Width" v-model="state.entryModel.width" min="0"
                                                   class="numberInput centered">
                                        </div>
                                        <div class="grid-col-3  place-self-center justify-self-start ml-2">
                                            <input v-on:keyup="keymonitor" @blur="calcUnits()"
                                                   onfocus="this.value=''" type="number" id="Height" v-model="state.entryModel.height" min="0"
                                                   class="numberInput centered">
                                        </div>
                                    </div>
                                </div>
                                <div id="TemplateUnits" v-show="(state.mode == 'each')">
                                    <div class="flex justify-content-center centered">
                                        <div class="col centered justify-content-center">
                                            <input v-on:keyup="keymonitor"  onfocus="this.value=''" type="number" id="units" v-model="state.entryModel.units" min="0" class="centered">
                                        </div>
                                    </div>
                                </div>
                                <div id="TemplateUnits" v-show="(state.mode == 'gallons')">
                                    <div class="flex justify-content-center centered">
                                        <div class="col centered justify-content-center">
                                            <input v-on:keyup="keymonitor"  onfocus="this.value=''" type="number" id="units" v-model="state.entryModel.units" min="0" class="centered">
                                        </div>
                                    </div>
                                </div>
                                <div id="TemplateBin" v-show="(state.mode == 'bin')">

                                    <div class="container centered">
                                        <div class="flex justify-content-center centered">
                                            <div class="col centered justify-content-center">
                                                <p style="font-size:30px;" class="centered">
                                                    {{state.entryModel.bin}}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="justify-content-center">
                                        <div class="col centered">
                                            <button id='selectBin' type="button" class="btn btn-primary selBtn center" @click="state.showBinDialog = true">Select Bin</button>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div>
                                <div >
                                    <h2 id="titleProducts">Destination</h2>
                                </div>
                            </div>

                            <div class="justify-content-center appendDestinationButtons">
                                <div class="flex centered">
                                    <div v-for="(destination, index) in state.destinations" :key="index">
                                        <ProductButtons :index="index" :class="[{'btn-primary': destination !== state.destination, 'btn-success': destination === state.destination,}]"
                                        @clicked="destinationClicked(destination)">{{destination}}</ProductButtons>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="grid grid-cols-9 justify-content-between">
                                <div class="col-span-3 grid grid-cols-subgrid details">
                                    <label class="">Date</label>
                                    <input  type="text" id="DateEntryPage" class="inputDetails date ml-4" maxlength="60"  v-model="state.entryModel.date"
                                            min="1970-01-01" disabled>
                                </div>

                                <div class="col-span-3 grid grid-cols-subgrid details ">
                                    <label>Company</label>
                                    <input disabled  type="text" id="Company" class="inputDetails ml-4" maxlength="60" v-model="state.entryModel.company"
                                           autocapitalize="off"
                                           autocomplete="off"
                                           spellcheck="false"
                                           autocorrect="off">
                                </div>
                                <div class="col-span-3 grid grid-cols-subgrid details ">
                                    <label>Product</label>
                                    <input disabled type="text" id="Product" class="inputDetails ml-4" maxlength="60"  v-model="state.entryModel.product"
                                           autocapitalize="off"
                                           autocomplete="off"
                                           spellcheck="false"
                                           autocorrect="off">
                                </div>
                            </div>

                            <div class="grid grid-cols-9 justify-content-between">
                                <div class="col-span-3 grid grid-cols-subgrid details">
                                    <label v-show="(state.mode == 'yards' || state.mode == 'bin')">Yards</label>
                                    <label v-show="(state.mode == 'each')">Each</label>
                                    <input disabled onfocus="this.value=''" type="number" id="Quantity" class="inputDetails ml-4" maxlength="100"
                                           v-model="state.entryModel.units">
                                </div>
                                <div class="col-span-3 grid grid-cols-subgrid details ">
                                    <label >Destination</label>
                                    <input  type="text" id="Destination" class="inputDetails ml-4" maxlength="60"
                                            v-model="state.entryModel.destination"
                                            autocapitalize="off"
                                            autocomplete="off"
                                            spellcheck="false"
                                            autocorrect="off" disabled>
                                </div>
                                <div class="col-span-3 grid grid-cols-subgrid details ">
                                    <label >Bin#</label>
                                    <input  type="text" id="Bin" class="inputDetails ml-4" maxlength="10"
                                            v-model="state.entryModel.bin"
                                            autocapitalize="off"
                                            autocomplete="off"
                                            spellcheck="false"
                                            autocorrect="off" disabled>
                                </div>
                            </div>

                            <div class=" justify-content-center">
                            </div>

                            <div class="grid grid-cols-12 justify-content-between">
                                <div class="col-span-8 centered">
                                    <button id="submit" type="button" class="btn btn-primary submit centered lrgBtn"
                                            @click="SubmitEntry()">SUBMIT</button>
                                </div>

                                <div class="col-span-4 ">
                                    <button class="comments btn btn-primary " @click="state.editComment = true">Comment:</button>
                                </div>
                            </div>
                        </div>
                    </div>
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

