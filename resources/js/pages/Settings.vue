<script setup>
import axios from 'axios';
import { onMounted, reactive, ref, watch } from 'vue';
import Dialog from '@/components/Dialog.vue';
import GenericButton from '@/components/GenericButton.vue';
import ProductButtons from '@/components/ProductButtons.vue';
import { useToastyStore } from '@/store/useToastyStore.js';
import { useUserStore } from '@/store/useUserStore.js';

const toastySettings = useToastyStore();


const fileInput = ref(null);
const state = reactive({
    delImage: -1,
    userNamesText: '',
    productSpecModels: [],
    productSortingSpecModels: [],
    binModels: [],
    imageList: [],
    selectedFiles: [],
    companyArray: [],
    showEditDialog: false,
    /*    entryModel: {
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
        imageList: [],
    },*/

/*    sortingModel: {
        user: 'Select User',
        units: '',
        product: '',
        date: 0,
        picked_timestamp: 0,
        company: '',
    },*/
    createProduct: {
        name: '',
        company: '',
        uom: 'each',
        id: -1,
        imageList: [],
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
        company: 'Lynden Door',
    },
    createItem: {},
    companies: [
        'Lynden Door',
        'Victory Millwork',
        'LD Trucking',
    ],
    deleteProductDialog: false,
    deleteImageDialog: false,
    newItem: false,
})
const user = useUserStore();

function productButton(product,index){
    state.selectedFiles = [];
    state.createItem = {
        edit: {
            name: product.name,
            uom: product.uom,
        },
        companyArray: product.company.split(','),
        company: '',
        imageList: product.imageList,
        model: 'Product',
        id: product.id,
        active: [false,false,false]
    };
    for (let i = 0; i < state.createItem.companyArray.length; i++) {
        const company = state.createItem.companyArray[i];
        const companyIndex = state.companies.indexOf(company);
        state.createItem.active[companyIndex] = true;
    }
    state.editIndex = index;
    state.showEditDialog = true;
    state.newItem = false;
}
function sortingProductButton(product){

    state.createItem = {
        edit: {
            name: product.name,
        },
        model: 'Sorting Product',
        id: product.id,
    };
    state.showEditDialog = true;
    state.newItem = false;
}

function binButton(bin, index){
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
    state.editIndex = index;
    state.showEditDialog = true;
    state.newItem = false;
}
function saveEdit(product) {
    if(product.companyArray.length === 0){
        toasty({ mode: 'warning', message: 'Must select one or more Companies' });
        return;
    }
    if (product.edit.name == '') {
        toasty({ mode: 'warning', message: 'Name Field Cannot Be Empty' });
    }
    else if (product.edit.uom == '') {
        toasty({ mode: 'warning', message: 'UOM Field Cannot Be Empty' });
    }
        state.createProduct.company = product.companyArray.toString().trim();
        state.createProduct.name = product.edit.name.trim();
        state.createProduct.uom = product.edit.uom.trim();
        state.createProduct.imageList = product.imageList;
        state.createProduct.id = product.id;
        axios({
            method: 'POST',
            url: '/saveProduct',
            data: state.createProduct,
            headers: {
                "Authorization": "Bearer " + localStorage.getItem('token'),
            }
        }).then((response) => {
            if(state.selectedFiles.length > 0){
                uploadImage(product);
                state.createProduct.imageList = product.imageList;
            }
            let message = 'Saved Edit: '
            if(response.data !== 'updated'){
                state.createProduct.id = response.data;
                state.productSpecModels.push({...state.createProduct});
                message = 'Created : '
            }
            else{
                state.productSpecModels[state.editIndex] = {...state.createProduct};

            }
            state.showEditDialog = false;
            toasty({ mode: 'success', message: message + product.edit.name });

        }, (error) => {
            if (error.message != undefined) {
                const tempError = error.response.data.error;
                toasty({ mode: 'error', message: tempError });
            }
        });
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
        }).then((resopnse) => {
            if(resopnse === 'updated'){
                return
            }
            state.createSortingProduct.id = resopnse.data;

        }, (error) => {
            if (error.message != undefined) {
                toasty({ mode: 'error', response: error, request: error.request, message: error.message });
            }
        });
    }
}

function validateYards(yards) {
    const yardsNumber = Number(yards);
    if(!isFinite(yardsNumber) || yards === ''){
        return 'Yards must be a number';
    }
    if (yardsNumber < 0) {
        return 'Yards must be a positive number';
    }
    if (yardsNumber >= 1000) {
        return 'Yards must be less than 1000';
    }
    if (Math.floor(yardsNumber * 10000) !== yardsNumber * 10000) {
        return 'Yards may only have 4 decimal places'
    }
    return null;
}

function saveBin(bin) {
    const yards = bin.edit.yards;
    const error = validateYards(yards);
    if(error != null){
        toasty({ mode: 'warning', message: error });
        return
    }
    if(bin.edit.binNumber == ''){
        toasty({ mode: 'warning', message: "Bin Number Field Cannot Be Empty" });
        return
    }
    if(bin.company == ''){
        toasty({ mode: 'warning', message: "A Company Must Be Selected" });
        return;
    }
    if(bin.location == ''){
        toasty({ mode: 'warning', message: "Location Field Cannot Be Empty" });
        return;
    }
    state.createBin.binNumber = bin.edit.binNumber.trim();
    state.createBin.company = bin.company.trim();
    state.createBin.yards =  bin.edit.yards;
    state.createBin.location = bin.edit.location.trim();
    state.createBin.id = bin.id;
    axios({
        method: 'POST',
        url: '/saveBin',
        data: state.createBin,
        headers: {
            "Authorization": "Bearer " + localStorage.getItem('token'),
        }
    }).then((response) => {
        if(response.data !== 'updated'){
            state.createBin.id = response.data;
            state.binModels.push({...state.createBin});
        }
        else{
            state.binModels[state.editIndex] = {...state.createBin};
        }
        state.showEditDialog = false;
        toasty({ mode: 'success', message: 'successfully saved Bin: ' + bin.edit.binNumber });
    }, (error) => {
            if (error.message != undefined) {
                const tempError = error.response.data.error;
                toasty({ mode: 'error', message: tempError });
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
    const names = usersNames.split(',');
    const map = new Map();
    for(let i = 0; i < names.length; i++){
        const name = names[i];
        if(map.has(name)){
            toasty({ mode: 'warning', message: 'Duplicate User Name: ' + name });
            return;
        }
        map.set(name, true);
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
    try {
    axios({
        method: 'GET',
        url: '/pickupProduct',
        headers: {
            "Authorization": "Bearer " + localStorage.getItem('token'),
        }
    }) .then((response) => {
        if (response.data.length >= 0) {
            state.productSpecModels = response.data;
            getImageList();
            localStorage.setItem('database_products', JSON.stringify(response.data));
            //getImageList();
        }
    }, (error) => {
        if (error.message != undefined) {
            toasty({ mode: 'error', response: error, request: error.request, message: error.message });
        }
    });
    } catch (error) {
        console.log(error);
    }
}
/*function getSortingProducts(){
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
}*/
function getPickupBins() {
    try {

    axios({
        method: 'GET',
        url: '/pickupBin',
        headers: {
            "Authorization": "Bearer " + localStorage.getItem('token'),
        }
    }).then((response) => {
        if (response.data.length > 0) {
            state.binModels = response.data;
            localStorage.setItem('database_bins', JSON.stringify(response.data));

        }
    }, (error) => {
        if (error.message != undefined) {
            toasty({ mode: 'error', response: error, request: error.request, message: error.message });
        }
    });
    }catch(error) {
        console.log(error);
    }
}

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
function deleteImage(product, index){
    try {
        const image = product.imageList[index];
        axios({
            method: 'post',
            url: '/deleteImage',
            headers: {
                "Authorization": "Bearer " + localStorage.getItem('token'),
            },
            data: image
        }).then(() => {
            product.imageList.splice(index, 1);
            toasty({ mode: 'success', message: 'successfully deleted image' });
        }, (error) => {
            console.error("Error deleting image:", error.details);
            toasty({ mode: 'error', message: error.message });
        })
        state.deleteImageDialog = false;
    } catch (error) {
        console.error("Error deleting image:", error);
        toasty({ mode: 'error', message: 'something went wrong' });
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
function newProduct(){
    state.createItem = {
        edit: {
            name: '',
            uom: '',
        },
        companyArray: [],
        imageList: [],
        model: 'Product',
        active: [false,false,false]
    }
    state.showEditDialog = true;
    state.newItem = true;
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
    state.newItem = true;
}
function newBin(){
    state.createItem = {
        edit: {
            binNumber: '',
            yards: 0,
            location: '',
        },
        company: 'Lynden Door',
        model: 'Bin',
    }
    state.showEditDialog = true;
    state.newItem = true;
}
function clickedCompanyButton(company, model, index) {

    if (model === 'Product') {
        const companyIndex = state.createItem.companyArray.indexOf(company);
        if (companyIndex < 0) {
            state.createItem.companyArray.push(company);
            state.createItem.active[index] = true;
        } else {
            state.createItem.companyArray.splice(companyIndex, 1);
            state.createItem.active[index] = false;
        }
    }
    else{
        state.createItem.company = company;
    }
}
function save(item){

    if (item.model === 'Product') {
        saveEdit(item);
    } else if (item.model === 'Sorting Product') {
        saveSorting(item);
    } else if (item.model === 'Bin') {
        saveBin(item);
    }
}

function handleDrop(e) {
    const files = Array.from(e.target.files);
    state.selectedFiles.push(...files);
}
function handleFileSelection(e) {
    const files = Array.from(e.target.files);
    state.selectedFiles.push(...files);
}
function triggerFileBrowse() {
    fileInput.value.click();
}

function uploadImage(product){
    try {
        //const images = [];
        const formData = new FormData();
        state.selectedFiles.forEach((file) => {
            if (file instanceof File) {
                formData.append('images[]', file);
            } else {
                console.error("Invalid file detected:", file);
            }
        });
        formData.append('product', product.edit.name);
        axios({
            method: 'post',
            url: '/imageUploads',
            data: formData,
            headers: {
                "Authorization": "Bearer " + localStorage.getItem('token'),
                "Content-Type": "multipart/form-data",
            }
        }).then((response) => {
            for(let i = 0; i < response.data.length; i++){
                const id = response.data[i][0];
                const image = response.data[i][1];
                const productName = product.edit.name;
                product.imageList.push({id: id, product: productName, imageName: image, src: 'storage/img/h96/uploads/' + productName + '/' + image, });
            }
            state.selectedFiles = [];
            toasty({mode: 'success', message: 'successfully uploaded images'});
        }, (error) => {
            console.error("Error uploading images:", error);
            toasty({mode: 'error', message: 'failed to upload images'});
        });
    } catch (error) {
        console.error("Error uploading images:", error);
        toasty({mode: 'error', message: 'failed to upload images'});
    }
}
function deleteProduct(product,index){
    let url;
    if(product.model === 'Product'){
       url = '/deleteProduct';
    }
    else if(product.model === 'Bin') {
        url = '/deleteBin';
    }
    else{
        url = '/deleteSortingProduct';
    }
    const id = product.id;
    axios({
        method: 'POST',
        url: url,
        data: {id: id, product: product.edit.name},
        headers: {
            "Authorization": "Bearer " + localStorage.getItem('token'),
        }
    }).then(() => {
        let tempName = '';
        if(product.model === 'Product'){
            state.productSpecModels.splice(index, 1);
            tempName = product.edit.name;
        }
        else if(product.model === 'Bin') {
            state.binModels.splice(index, 1);
            tempName = product.edit.binNumber;
        }
        toasty({mode: 'success', message: 'successfully deleted: ' + tempName});
    })
    state.showEditDialog = false;
    state.deleteProductDialog = false;

}
watch( () => user.userNameList, (newVal) => {
    const tempNames = newVal;
    const index = tempNames.indexOf('Select User');
    if (index > -1) {
        tempNames.splice(index, 1);
    }
    state.userNamesText = tempNames.join(',');
})

onMounted( () => {
    const tempNames = user.userNameList;
    const index = tempNames.indexOf('Select User');
    if (index > -1) {
        tempNames.splice(index, 1);
    }
    state.userNamesText = tempNames.join(',');
    getPickupProduct();
    //getSortingProducts();
    getPickupBins();
})
</script>

<template>
    <Dialog id="editItem" v-if="state.showEditDialog" :size="'xl'" :dialogVisible="state.showEditDialog"
            :title="state.createItem.model" class="fixed inset-0 z-50">
        <div v-if="state.createItem.model === 'Bin'" class="flex flex-wrap centered">
            <div v-for="(company, index) in state.companies" :key="index">
                <ProductButtons :id="'editItemCompany-' + index" @clicked="clickedCompanyButton(company,state.createItem.model)" :active="state.createItem.company === company">{{company}}
                </ProductButtons>
            </div>
        </div>
        <div v-if="state.createItem.model === 'Product'" >
            <div class="flex flex-wrap centered">
                <div v-for="(company, index) in state.companies" :key="index">
                <ProductButtons :id="'editItemCompanies-'+index"  @clicked="clickedCompanyButton(company,state.createItem.model,index)" :active="state.createItem.active[index]">{{company}}
                </ProductButtons>
                </div>
            </div>
            <div>
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <label class="text-md px-4">Name:</label>
                    <input :id="'editItemName'" v-model="state.createItem.edit.name" class="inputDetails  mx-2 px-1" placeholder="name">
                </div>
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <label class="text-md px-4">UOM:</label>
                    <select :id="'editItemUom'" v-model="state.createItem.edit.uom" class="inputDetails mx-2 px-1">
                        <option value="each" class="dark:text-black">Each</option>
                        <option value="yards" class="dark:text-black">Yards</option>
                    </select>
                </div>
            </div>
        </div>
        <div v-if="state.createItem.model !== 'Product'" class="flex flex-wrap centered">
            <div v-for="(value, index) in Object.keys(state.createItem.edit)" :key="index">
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <label class="text-md px-4">{{value}}:</label>
                    <input :id="'editItemInput-' + index" v-model="state.createItem.edit[value]" class="inputDetails  mx-2 px-1" :placeholder="value">
                </div>
            </div>
        </div>
        <hr>
        <div class="overflow-auto max-h-[calc(50vh-100px)]" v-if="state.createItem.model === 'Product'">

            <div class="flex flex-wrap centered mb-24">
                <p class="ml-2">Images:</p>
                <div class="grid grid-cols-12 menuSpacer ">
                    <div class="col-span-3 ml-2">
                        <form class="upload" @submit.prevent="" enctype="multipart/form-data">
                            <div class="drop"
                                @dragover.prevent
                                @drop.prevent="handleDrop( $event,state.createItem)">
                                Drop Here
                                <a @click="triggerFileBrowse">Browse</a>
                                <input ref="fileInput"
                                    type="file"
                                    name="upl"
                                    multiple
                                    hidden
                                    @change="handleFileSelection( $event,state.createItem)" />
                            </div>
                            <ul>
                                <li v-for="(file, index) in state.selectedFiles" class="text-white" :key="index">
                                    {{ file.name }} test
                                </li>
                            </ul>
                            <input
                                class="uploadName"
                                type="hidden"
                                name="product_name"
                                v-model="state.createItem.edit.name"
                            />
                        </form>
                    </div>
                    <div class="col-span-9 col-start-5">
                        <div class="columns-2 gap-4"
                             style="grid-auto-flow: dense;">
                            <div v-for="(item, index) in state.createItem.imageList" :key="index" class="flex flex-col items-center break-inside-avoid mb-4">
                                <img :src="item.src" alt=" " class="img-thumbnail my-2" />
                                <button type="button" class="btn bg-red-800 mx-4 mb-4" @click="state.deleteImageDialog = true; state.delImage = index">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex justify-center">
            <button id="CancelEdit" class="btn btn-primary justify-center" :class="[
                        'absolute',
                        'bottom-2',

                        'h-8',
                        'w-32',
                        'inline-flex',
                        'm-4',
                        'my-[2px]',
                        'py-[3px]']" @click="state.showEditDialog = false">
                <p class="text-md centered w-full relative whitespace-nowrap">Cancel</p>
            </button>
        </div>
        <div v-if="!state.newItem">
            <button id="deleteItem" class="btn" :class="[
                    'absolute',
                    'bottom-2',
                    'left-0',
                    'h-8',
                    'w-32',
                     'inline-flex',
                     'm-4',
                     'my-[2px]',
                     'bg-red-800',
                     'py-[3px]']" @click="state.deleteProductDialog = true">
                <p class="text-md centered w-full relative whitespace-nowrap">Delete</p>
            </button>
        </div>
        <div>
            <button id="saveEdit" class="btn btn-primary" :class="[
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
    <Dialog v-if="state.deleteProductDialog" :size="'sm'" :dialogVisible="state.deleteProductDialog" :title="'Delete Product'"
            class="fixed inset-0 z-50">
        <p class="text-xl text-zinc-700 mt-1 ml-3 mr-4 dark:text-gray-200 whitespace-nowrap text-center">Are you
            sure you want to delete this product?</p>
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
                ]" @click="deleteProduct(state.createItem, state.editIndex)">
                <p class="text-md text-center w-full relative top-[2px] whitespace-nowrap">Delete</p>
            </GenericButton>
        </div>
    </Dialog>
    <Dialog v-if="state.deleteImageDialog" :size="'sm'" :dialogVisible="state.deleteImageDialog" :title="'Delete Image'"
            class="fixed inset-0 z-50">
        <p class="text-xl text-zinc-700 mt-1 ml-3 mr-4 dark:text-gray-200 whitespace-nowrap text-center">Are you
            sure you want to delete this Image?</p>
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
                ]" @click="state.deleteImageDialog = false">
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
                ]" @click="deleteImage(state.createItem,state.delImage)">
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
                    <button hidden id="CreateSorting" type="button" class="btn btn-primary selBtn center" @click="newSortingProduct()">Create New Sorting</button>
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col">
                    <h2 id="titleProducts">Products</h2>
                </div>
            </div>

            <div  class="flex flex-wrap mx-4 w-full centered place-content-center">
                <div v-for="(product, index) in state.productSpecModels" :key="index">
                    <ProductButtons :id="'editProduct-'+index" :product="product" :index="index" :disabled="false"
                                    @clicked="productButton(product, index)">{{product.name}}</ProductButtons>
                </div>
            </div>
            <hr>
            <div hidden class="row">
                <div class="col">
                    <h2 id="titleProducts">Sorting Products</h2>
                </div>
            </div>

            <div hidden  class="flex flex-wrap mx-4 centered place-content-center">
                <div v-for="(product, index) in state.productSortingSpecModels" :key="index">
                    <ProductButtons :id="'editSorting-'+index" :disabled="false"
                                    @clicked="sortingProductButton(product)">{{product.name}}</ProductButtons>
                </div>
            </div>

            <hr hidden>


            <div class="row justify-content-center">
                <div class="col-4">
                    <h2 id="titleProducts">Bin Numbers</h2>
                </div>
            </div>
            <div  class="flex flex-wrap mx-4 centered place-content-center">
                <div v-for="(bin,index) in state.binModels" :key="bin.binNumber">
                    <ProductButtons :id="'editBin-'+index" class="settingsBin" @clicked="binButton(bin,index)">{{bin.binNumber}}</ProductButtons>
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
                              class="border-black border-2 ml-1 px-1"
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
input, select{
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
