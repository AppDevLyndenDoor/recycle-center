<script setup>
import '../../css/app.css';
import '../../css/style.css';
import axios from 'axios';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import Dialog from '@/components/Dialog.vue';
import ProductButtons from '@/components/ProductButtons.vue';
import { post_to_server } from '@/majax.js';
import {useOfflineStore} from '@/store/useOfflineStore.js';
import {useToastyStore} from '@/store/useToastyStore.js';
import { useUserStore } from '@/store/useUserStore.js';


defineEmits(['offlinePost']);
const user = useUserStore();
const toastySettings = useToastyStore();
const offlineStore = useOfflineStore();
let pressTimer = window.setTimeout(() => {}, 0);
const textareaRows = computed(() => (user.maxDialog === 'xl' ? 10 : 4));
const textareaCols = computed(() => (user.maxDialog === 'xl' ? 52 : 30));
const state = reactive( {
    showBinDialog: false,
    showImage: false,
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

    binModels: {
        'Lynden Door': [],
        'Victory Millwork': [],
        'LD Trucking': [],
    },
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
    imageList: [],
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
    serverImageList: {},
    uploadResults: 0,
    uploadQuantity: 0,
    imageDeleteList: [],

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
    const width = parseFloat(state.entryModel.width) || 0;
    const length = parseFloat(state.entryModel.length) || 0;
    const height = parseFloat(state.entryModel.height) || 0;
    state.entryModel.units = Math.round(width * length * height / 1728 / 27 * 10000) / 10000;
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
        productButton(state.productSpecModels[0]);
    }
}

function productButton(product){
    state.entryModel.product = product.name;
    state.entryModel.uom = product.uom;
    state.mode = product.uom;
    state.entryModel.length = 0;
    state.entryModel.width = 0;
    state.entryModel.height = 0;
    state.entryModel.units = 0;
    state.entryModel.bin = '';
}
function getRandomInt(max){
    return Math.floor(Math.random() * max)+1;
}
function enteryValidation(){
    if (state.entryModel.user == 'Recycle Center' || state.entryModel.user == 'Select User' || state.entryModel.user == 'Op Erator') {
        return 'Please Select a User Name'
    }

    if (state.mode == 'each' && (!state.entryModel.units || state.entryModel.units <= 0 )) {
        return 'Each must be greater than 0'
    }
    const length = state.entryModel.length;
    const width = state.entryModel.width;
    const height = state.entryModel.height;
    if(state.mode == 'yards' ) {
        if ( (length <= 0)) {
            return 'Length must be greater than 0'
        }
        else if (width <= 0){
            return 'Width must be greater than 0'
        }
        else if (height <= 0){
            return 'Height must be greater than 0'
        }
    }
    else if(state.mode == 'bin' && state.entryModel.bin == '') {
        return 'Please select a Bin'
    }
    return '';
}
function SubmitEntry(){
    const error = enteryValidation();
    if(error !== ''){
        toasty({ mode: 'warning', response: error, message: error});
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
            toasty({ mode: 'success', message: 'Entry Submitted' });
        },
        error: function (error)  {
            const temp = error.response.data.error;
            toasty({ mode: 'warning', message: temp});
        },
        complete: function ()  { },
    };
    post_to_server(post,offlineStore);

    state.entryModel.length = 0;
    state.entryModel.width = 0;
    state.entryModel.height = 0;
    state.entryModel.bin = '';
    state.entryModel.units = 0;
    state.entryModel.comment = '';
    state.entryModel.picked_timestamp = 0;
    state.newComment = '';
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
                product.imageList = [];
            }
            getImageList();
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
            for(const bin of response.data) {
                state.binModels[bin.company].push(bin);
            }
            //state.binModels = response.data;
            localStorage.setItem('database_bins', JSON.stringify(response.data));
        }
    }, (error) => {
        if (error.message != undefined) {
            toasty({ mode: 'error', response: error, request: error.request, message: error.message });
        }
    });
}


watch( () => user.pseudonym, (newUser) => {
    if(newUser !== undefined){
        state.entryModel.date = CurrentDate()
        state.entryModel.user = newUser;

    }
})
function getImageList(){
    axios({
        method: 'GET',
        url: '/imageUploads',
        headers: {
            "Authorization": "Bearer " + localStorage.getItem('token'),
        }
    }).then((response) => {
        state.imageList = response.data;
        const tempList = response.data;
        for(let index = 0; index < state.productSpecModels.length; index++){
            const product = state.productSpecModels[index];
            product.imageList = (product.imageList === undefined) ?  [] : product.imageList;
            let i = 0;
            while(i < tempList.length ){
                const image = tempList[i];
                if(product.name === image.product){
                    image.src = 'storage/img/h96/uploads/' + image.product + '/' + image.imageName;
                    product.imageList.push(image);
                    tempList.splice(i, 1);
                    if (tempList.length === 0)  return;
                }
                else{
                    i++;
                }
            }
        }
    }, (error) => {
        toasty({
            mode: 'error',
            response: error,
            request: error.request,
            message: error.message,
        });

    })
}
function startTimer(index){
    pressTimer = setTimeout(() => showImage(index), 1000);
}
function stopTimer(){
    clearTimeout(pressTimer);
}
function showImage(id){
    state.imageList = state.productSpecModels[id].imageList;
    state.showImage = true;

}

onMounted(() => {
    if(user.userName !== undefined) {
        state.entryModel.date = CurrentDate()
        if (!user.perms.admin && user.perms.operator) {
            state.entryModel.user = user.pseudonym;
        } else {
            state.entryModel.user = user.pseudonym;
        }
    }
    getPickupProduct();
    getPickupBins();

});

</script>

<template>

    <Dialog v-if="state.showBinDialog" :size="'md'" :dialogVisible="state.showBinDialog"
            :title="'Bin'" class="fixed inset-0 z-50">
        <div class="flex flex-wrap ">
            <div class="flex flex-wrap max-h-[calc(75vh)] xl:max-h-[calc(100vh-250px)] 2xl:max-h-[calc(100vh-445px)] w-full overflow-auto mx-2 mb-24">
            <div v-for="(bin,index) in state.binModels[state.entryModel.company]" :key="bin.binNumber">
                <ProductButtons :id="'binButtons-'+index" @clicked="pickBin(bin)">{{bin.binNumber}}</ProductButtons>
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
    <Dialog v-if="state.editComment" :size="'md'" :dialogVisible="state.editComment" :title="'Comment'" class="fixed inset-0 z-50 ">
        <div class="flex flex-wrap centered">
            <textarea id="commentTextarea" :rows="textareaRows" :cols="textareaCols" v-model="state.newComment"
                      class="border-black text-3xl border-2 px-2 dark:text-black lg:text-lg mx-2"
                      autocapitalize="off"
                      autocomplete="off"
                      spellcheck="false"
                      autocorrect="off">
            </textarea>
        </div>
        <div>
            <button id="closeButton" class="btn btn-primary" :class="[
                        'absolute',
                        'bottom-2',
                        'right-0',
                        'h-8',
                        'w-32',
                        'inline-flex',
                        'm-4',
                        'my-[2px]',
                        'py-[3px]',
                    ]" @click="state.editComment = false; state.entryModel.comment = state.newComment;">
                <p class="text-md centered w-full relative whitespace-nowrap">confirm</p>
            </button>
        </div>
        <div>
            <button id="saveComment" class="btn btn-primary" :class="[
                        'absolute',
                        'bottom-2',
                        'left-0',
                        'h-8',
                        'w-32',
                        'inline-flex',
                        'm-4',
                        'my-[2px]',
                        'py-[3px]',
                    ]" @click="state.editComment = false; state.newComment = state.entryModel.comment">
                <p class="text-md centered w-full relative whitespace-nowrap">close</p>
            </button>
        </div>
    </Dialog>
    <Dialog v-if="state.showImage" :size="(user.maxDialog !== 'xl' ? user.maxDialog:'md')" :dialogVisible="state.showImage" :title="'Images'" class="fixed inset-0 z-50">
        <div class="col-span-9 col-start-5 overflow-auto max-h-[calc(60vh)] xl:max-h-[calc(100vh-200px)] 2xl:max-h-[calc(100vh-445px)] mb-12">
            <div class="columns-2 gap-4"
                 style="grid-auto-flow: dense;">
                <div v-for="(item, index) in state.imageList" :key="index" class="flex flex-col items-center break-inside-avoid mb-4 mx-2">
                    <img :src="item.src" alt=" " class="img-thumbnail my-2" />
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
                    ]" @click="state.showImage = false">
                <p class="text-md centered w-full relative whitespace-nowrap">Close</p>
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
                                        <ProductButtons :id="'companyButtons-'+index" @clicked="clickedCompanyButtons('entryModel', index)" :active="state.entryModel.company === company">{{company}}
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
                            <div  class="flex flex-wrap w-full centered place-content-center">
                                <div v-for="(product, index) in state.productSpecModels" :key="index">
                                    <ProductButtons :id="'productButtons-'+index" :product="product"
                                                    :index="index" :disabled="product.disabled" :active="product.name === state.entryModel.product"
                                                    @mousedown="startTimer(index)"
                                                    @mouseup="stopTimer()"
                                                    @mouseleave="stopTimer()"
                                                    @touchstart="startTimer(index)"
                                                    @touchend="stopTimer()"
                                                    @clicked="productButton(product, index)"
                                    >{{product.name}}</ProductButtons>
                                </div>
                            </div>
                            <!--                                                    -->
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
                                                    type="number" id="Length" v-model="state.entryModel.length" min="0"
                                                   class="centered">
                                        </div>
                                        <div class="grid-col-3  place-self-center justify-self-start ml-2">
                                            <input v-on:keyup="keymonitor" @blur="calcUnits()"
                                                    type="number" id="Width" v-model="state.entryModel.width" min="0"
                                                   class="centered">
                                        </div>
                                        <div class="grid-col-3  place-self-center justify-self-start ml-2">
                                            <input v-on:keyup="keymonitor" @blur="calcUnits()"
                                                    type="number" id="Height" v-model="state.entryModel.height" min="0"
                                                   class="centered">
                                        </div>
                                    </div>
                                </div>
                                <div id="TemplateUnits" v-show="(state.mode == 'each')">
                                    <div class="flex justify-content-center centered">
                                        <div class="col centered justify-content-center">
                                            <input v-on:keyup="keymonitor"   type="number" id="eachUnits" v-model="state.entryModel.units" min="0" class="centered">
                                        </div>
                                    </div>
                                </div>
                                <div id="TemplateUnits" v-show="(state.mode == 'gallons')">
                                    <div class="flex justify-content-center centered">
                                        <div class="col centered justify-content-center">
                                            <input v-on:keyup="keymonitor"  onfocus="this.value=''" type="number" id="gallonsUnits" v-model="state.entryModel.units" min="0" class="centered">
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
                                <div class="flex flex-wrap  centered">
                                    <div v-for="(destination, index) in state.destinations" :key="index">
                                        <ProductButtons :id="'destinationButtons-'+index" :index="index" :active="destination === state.entryModel.destination"
                                        @clicked="destinationClicked(destination)">{{destination}}</ProductButtons>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="grid grid-cols-6 justify-content-between ml-2 md:grid-cols-9">
                                <div class="col-span-3 grid grid-cols-subgrid details justify-content-between">
                                    <div class="grid col-span-3 md:col-span-2 grid-cols-1 xl:grid-cols-2 ">
                                    <label class="centered">Date</label>
                                    <input  type="text" id="DateEntryPage" class="inputDetails date pl-2 ml-8 xl:ml-0" maxlength="60"  v-model="state.entryModel.date"
                                            min="1970-01-01" disabled>
                                    </div>
                                </div>

                                <div class="col-span-3 grid grid-cols-subgrid details ">
                                    <div class="grid col-span-3 md:col-span-2 grid-cols-1 xl:grid-cols-2">
                                    <label class="centered">Company</label>
                                    <input disabled  type="text" id="Company" class="inputDetails pl-2 ml-8 xl:ml-0" maxlength="60" v-model="state.entryModel.company"
                                           autocapitalize="off"
                                           autocomplete="off"
                                           spellcheck="false"
                                           autocorrect="off">
                                    </div>
                                </div>
                                <div class="col-span-3 grid grid-cols-subgrid details ">
                                    <div class="grid col-span-3 md:col-span-2 grid-cols-1 xl:grid-cols-2">
                                    <label class="centered">Product</label>
                                    <input disabled type="text" id="Product" class="inputDetails pl-2 ml-8 xl:ml-0" maxlength="60"  v-model="state.entryModel.product"
                                           autocapitalize="off"
                                           autocomplete="off"
                                           spellcheck="false"
                                           autocorrect="off">
                                    </div>
                                </div>
<!--                            </div>

                            <div class="grid grid-cols-9 justify-content-between ml-2 centered">-->
                                <div class="col-span-3 grid grid-cols-subgrid details">
                                    <div class="grid col-span-3 md:col-span-2 grid-cols-1 xl:grid-cols-2">
                                    <label v-show="(state.mode == 'yards' || state.mode == 'bin')" class="centered">Yards</label>
                                    <label v-show="(state.mode == 'each')" class="centered">Each</label>
                                    <input disabled onfocus="this.value=''" type="number" id="Quantity" class="inputDetails pl-2 ml-8 xl:ml-0" maxlength="100"
                                           v-model="state.entryModel.units">
                                    </div>
                                </div>
                                <div class="col-span-3 grid grid-cols-subgrid details ">
                                    <div class="grid col-span-3 md:col-span-2 grid-cols-1 xl:grid-cols-2">
                                    <label class="centered">Destination</label>
                                    <input  type="text" id="Destination" class="inputDetails pl-2 ml-8 xl:ml-0" maxlength="60"
                                            v-model="state.entryModel.destination"
                                            autocapitalize="off"
                                            autocomplete="off"
                                            spellcheck="false"
                                            autocorrect="off" disabled>
                                    </div>
                                </div>
                                <div class="col-span-3 grid grid-cols-subgrid details">
                                    <div class="grid col-span-3 md:col-span-2 grid-cols-1 xl:grid-cols-2">
                                    <label class="centered">Bin#</label>
                                    <input  type="text" id="Bin" class="inputDetails pl-2 ml-8 xl:ml-0" maxlength="10"
                                            v-model="state.entryModel.bin"
                                            autocapitalize="off"
                                            autocomplete="off"
                                            spellcheck="false"
                                            autocorrect="off" disabled>
                                    </div>
                                </div>
                            </div>

                            <div class=" justify-content-center">
                            </div>

                            <div class="grid grid-cols-12 justify-content-between">
                                <div class="col-span-7 md:col-span-8 centered">
                                    <button id="submit" type="button" class="btn btn-primary submit centered"
                                            @click="SubmitEntry()">SUBMIT</button>
                                </div>

                                <div class="col-span-4 md:col-span-4">
                                    <button id="commentButton" class="comments btn btn-primary md:ml-2 " @click="state.editComment = true">Comment:</button>
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

