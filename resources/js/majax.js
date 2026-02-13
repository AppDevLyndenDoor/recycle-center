import axios from 'axios';
// import pinia from '@/store/piniaInstance.js'
// import {useOfflineStore} from '@/store/useOfflineStore.js';
//import {useOfflineStore} from '@/store/useOfflineStore.js';



/**
 Custom axios functions.  Allows queueing and post triggering upon successful axios calls
 */
let connectionStatus = 1;

//Hacked the bower library to return to this far (look for json-stable-strinfiy)
let stringify = {};

//Loading Spinner options
let spinner = {};
let spinneropts = {
  lines: 13, // The number of lines to draw
  length: 20, // The length of each line
  width: 10, // The line thickness
  radius: 30, // The radius of the inner circle
  corners: 1, // Corner roundness (0..1)
  rotate: 0, // The rotation offset
  direction: 1, // 1: clockwise, -1: counterclockwise
  color: '#000', // #rgb or #rrggbb or array of colors
  speed: 1, // Rounds per second
  trail: 60, // Afterglow percentage
  shadow: false, // Whether to render a shadow
  hwaccel: false, // Whether to use hardware acceleration
  className: 'spinnering', // The CSS class to assign to the spinner
  zIndex: 2e9, // The z-index (defaults to 2000000000)
  top: 'auto', // Top position relative to parent in px
  left:'auto' // Left position relative to parent in px
};



//Manages an axios queue, prevents overloading things
let axiosq = (function (queue, options)
{
    // Initialize storage for request queues if it's not initialized yet
    if (typeof document.axiosq == "undefined") document.axiosq = {q:{}, r:null};

    // Initialize current queue if it's not initialized yet
    if (typeof document.axiosq.q[queue] == "undefined") document.axiosq.q[queue] = [];

    if (typeof options != "undefined") // Request settings are given, enqueue the new request
    {
        // Copy the original options, because options.complete is going to be overridden

        let optionsCopy = {};
        for (let o in options) optionsCopy[o] = options[o];
        options = optionsCopy;

        // Override the original callback

        let originalCompleteCallback = options.complete;

        options.complete = function (request, fstatus)
        {
            // Dequeue the current request
            document.axiosq.q.main_queue.shift ();
            document.axiosq.r = null;

            // Run the original callback
            if (originalCompleteCallback) originalCompleteCallback (request, fstatus);
            // Run the next request from the queue
            if (document.axiosq.q.main_queue.length > 0){
                options = document.axiosq.q.main_queue[0];
                document.axiosq.r = axios(options)
                    .then((data) => options.success(data, options.offlineObj))
                    .catch(options.error)
                    .finally(options.complete);
            }
        };

        // Enqueue the request
        document.axiosq.q.main_queue.push (options);
        // Also, if no request is currently running, start it
        if (document.axiosq.q.main_queue.length == 1) document.axiosq.r = axios(options)
            .then((data) => options.success(data,options.offlineObj))
            .catch(options.error)
            .finally(options.complete);
    }
    else // No request settings are given, stop current request and clear the queue
    {
        if (document.axiosq.r)
        {
            debugger;
            document.axiosq.r.abort ();
            document.axiosq.r = null;
        }

        document.axiosq.q.main_queue = [];
    }
});

// Hooks the axios Queue, when the connection status changes to connected,
//      attempts to post all offline content

let majax = (function(obj) {
    let oldsuccess = obj.success;
    let olderror = obj.error;
    let oldstatus = connectionStatus;

    //if (typeof obj.timeout == "undefined") obj.timeout = 6000;

    obj.success = (function(data, offlineObj) {
        connectionStatus = 1;
        //If we were previously not connected, post the localstorage
        debugger
        let ret = oldsuccess(data,offlineObj);
        if(oldstatus == 0){
            post_all();
        }
        return ret;
    });
    obj.error = (function(a, b, c) {
        connectionStatus = 0;
        if (olderror != undefined) {
            return olderror(a, b, c);
        }
    });

    axiosq("main_queue",obj);
});

/**
 * post_to_server
 * Takes in a single axios request, compares to the queue, and conditionally adds it
 **/
export function post_to_server(post, offlinePosts){
    //Skip if already queued
    let queue = offlinePosts.offlinePosts;
    for(let i in queue){
        let jsonObj = JSON.stringify(queue[i]);
        if (jsonObj == JSON.stringify(post)){
            return false;
        }
    }
    const obj = Object.assign({},post);
    offlinePosts.offlinePosts.push(obj);
    if(offlinePosts.offline) return
    post_one(post,offlinePosts)
    return true;
}

/**
 * Generic success callback builder
 * Uses the url to generate a default callback.  This is needed for rebuilding
 *   the queue after pulling the data from localstorage (which does not support
 *   functions)
 **/
// function retrieve_generic_success(url){
//     if(url == '/manifest'){
//         return function(data){
//             if(data.result){
//                 toastr.success('Saved Manifest ID '+data.id);
//             }
//         };
//     }
//     return function(){};
// }

/**
 * post_one
 * builds and submits a queued axios call.  Populates some data from the object
 * passed and sets many non default settings
 **/
function post_one(data,offlinePosts){
    let callback = data['success'];
    let errorCallback = data['error'];
    data['method'] = data['method'] || 'GET';
    majax({
        xhrFields: {
            withCredentials: true,
        },
        offlineObj: data,
        headers : data['headers'],
        url : data['url'],
        method: data['method'],
        data: data['data'],
        timeout : 3000,
        success : function(data,obj) {
            debugger;
            if(data.result == false){
                localStorage.setItem('OfflinePosts',JSON.stringify(offlinePosts.offlinePosts));
                //toasty.error('Could not sync to server, try again later');
                return false;
            }
            for(let i = 0; i < offlinePosts.offlinePosts.length; i++){
                let post = offlinePosts.offlinePosts[i];
                if (JSON.stringify(post) == JSON.stringify(obj)){
                    offlinePosts.offlinePosts.splice(i,1);
                }
            }
            callback(data);
            return true;
        },
        error: function(data){
            errorCallback(data);
        }
    });
}

export function post_all(offlinePosts){
    const posts = offlinePosts.offlinePosts
    if(offlinePosts.offlinePosts.length === 0) return;
    for(let i in posts){
        post_one(posts[i],offlinePosts);
    }
}
