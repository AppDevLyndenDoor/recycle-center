<!--
Toasty is a simple notification component
It is passed an object called "toasty" as a prop
This conatins a custom message and a mode.
The mode configures the styles to display errors, success, or warning messages
-->
<template>
  <div v-if="visible" :class="[
  'fixed',
  'md:right-0',
  {
    'bottom-0': !popup,
    'bottom-[-100px]': popup,
  },
  'transition',
  'ease-in-out',
  'z-[99999999]',
  'duration-[300ms]',
  ]" style="transition-property: bottom;">
    <div
      :class="[
        'bg-gradient-to-b',
        'bg-black',
        {
          'from-red-500': mode == 'error',
          'to-red-500/80': mode == 'error',
          'from-green-500': mode=='success',
          'to-green-500/80': mode=='success',
          'from-yellow-500': mode=='warning',
          'to-yellow-500/80': mode=='warning',
          'from-blue-500': mode=='info',
          'to-blue-500/80': mode=='info',
          'opacity-0': fading,
          'opacity-100': !fading,
        },
        'transition',
        'ease-in-out',
        'duration-[1000ms]',
        'grid',
        'grid-cols-1',
        'md:w-64',
        'w-screen',
        'h-24',
        'p-3',
        'rounded-lg',
        ]">
      <p :class="[
        'relative',
        'text-md',
        'text-white',
        'select-none',
        'ellipses',
        {
          'bottom-2':mode=='error',

        },
      ]"><b>{{ PrettyMode }}:&nbsp;</b>{{ message }}</p>
      <p v-if="mode=='error'" class="text-white absolute right-2 text-sm bottom-0 cursor-pointer hover:text-sky-300" @click="setTimer()">Close</p>
      <p v-if="mode=='error'" class="text-white absolute right-14 text-sm bottom-0 cursor-pointer hover:text-sky-300" @click="report()">Report</p>
    </div>
  </div>

</template>

<script setup>
import { ref, watch, inject, computed } from 'vue';
import {useToastyStore} from '@/store/useToastyStore.js';

const toastySettings = useToastyStore();

const message = ref('');
const response = ref('');
const request = ref('');
const visible = ref(false);
const mode = ref('info');
const fading = ref(false);
const delay = ref(5000);
const fadeout = ref(1000);
const popup = ref(true);


watch(() => toastySettings.visible , () => {
  if (toastySettings.visible == true){
    popup.value = true;
    message.value = JSON.stringify(toastySettings.message);
    request.value = toastySettings.request;
    response.value = toastySettings.response;
    mode.value = toastySettings.mode;
    fading.value = false;
    visible.value = true;

    // set pop up timer. This might also make sense as a "NextTick" function
    setTimeout(function () {
      popup.value = false;
    }, 10);
    // clear and timeouts for the fading functionality
    if (window.toastyTimeoutHandle || window.toastyTimeoutHandle2) {
      window.clearTimeout(window.toastyTimeoutHandle);
      window.clearTimeout(window.toastyTimeoutHandle2);
      fading.value = false;
    }
    // start the timers for fading and unmounting if not an error message
    if (mode.value != 'error') {
      delay.value = toastySettings.delay || 5000;

      window.toastyTimeoutHandle = setTimeout(function () {
        fading.value = true;
      }, delay.value);
      window.toastyTimeoutHandle2 = setTimeout(function () {
        fading.value = false;
        visible.value = false;
      }, delay.value + fadeout.value);
    } else {
      // log the error to the console
        console.log("error:" + message.value);
    }
  }
  // flip the flag back to visible so it can be reversed again later by other calls to summon toasty
  toastySettings.visible = false;
})

// When this component is mounted and the settings prop is provided, we can optionally pass delay or fadeout. Otherwise they will use default values.


// set a timer manually (i,e if someone clicks a close button)
function setTimer(){
  if (window.toastyTimeoutHandle2) {
    window.clearTimeout(window.toastyTimeoutHandle2)
  }
  fading.value = true
  popup.value = true
  window.toastyTimeoutHandle2 = setTimeout(function () { visible.value = false; fading.value = false }, fadeout.value)
}

// if the user clicks the report button on an error notification, we use the magic event bus to summon the report dialog on the mainlayout, we also call setTimer so that the dialog will close.
/*function report(){
  $eventBus.MainLayout.reporting.visible = true;
  $eventBus.MainLayout.reporting.message = message.value;
  $eventBus.MainLayout.reporting.request = request.value;
  $eventBus.MainLayout.reporting.response = response.value;
  setTimer();
}*/

// Gives us a capitalized version of the current mode
const PrettyMode = computed(()=>{
    const str = mode.value;
    return str.charAt(0).toUpperCase() + str.slice(1);
})

</script>

<style>
.ellipses{
    display: -webkit-box;
    max-width: 250px;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
