<?php

use App\Http\Controllers\EntryController;
use App\Http\Controllers\SortingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $user = auth()->user();

    return ! $user ? Inertia::render('auth/Login', []) : Inertia::render('Entry', []);
});

Route::get('dashboard', function () {
    return Inertia::render('Entry', []);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('viewEntries', function () {
    return Inertia::render('ViewEntries', []);
})->middleware(['auth', 'verified'])->name('viewEntry');
Route::get('sorting', function () {
    return Inertia::render('Sorting', []);
})->middleware(['auth', 'verified'])->name('sorting');
Route::get('viewSorting', function () {
    return Inertia::render('ViewSorting', []);
})->middleware(['auth', 'verified'])->name('viewsorting');
Route::get('settings', function () {
    return Inertia::render('Settings', []);
})->middleware(['auth', 'verified'])->name('Settings');

Route::get('/ping', [EntryController::class, 'ping'])->middleware(['auth', 'verified'])->name('ping');

Route::get('/pickupUserNames', [EntryController::class, 'getPickupUserNames'])->middleware(['auth', 'verified'])->name('pickupUserNames');
Route::Post('/saveUserNames', [EntryController::class, 'saveUserNames'])->middleware(['auth', 'verified'])->name('saveUserNames');

Route::post('/pickupUnit', [EntryController::class, 'submitPickupUnit'])->middleware(['auth', 'verified'])->name('submitPickupProduct');
Route::get('/pickupUnitRange', [EntryController::class, 'getPickupUnitRange'])->middleware(['auth', 'verified'])->name('PickupUnitRange');
Route::Post('/saveProduct', [EntryController::class, 'saveProduct'])->middleware(['auth', 'verified'])->name('saveProduct');
Route::get('/pickupProduct', [EntryController::class, 'getPickupProduct'])->middleware(['auth', 'verified'])->name('pickupProduct');
Route::Post('/saveEntriesEdits', [EntryController::class, 'saveEntriesEdits'])->middleware(['auth', 'verified'])->name('saveEntriesEdits');

Route::get('/pickupBin', [EntryController::class, 'getPickupBin'])->middleware(['auth', 'verified'])->name('pickupBin');
Route::post('/saveBin', [EntryController::class, 'saveBin'])->middleware(['auth', 'verified'])->name('saveBin');

Route::post('/pickupSortingProduct', [SortingController::class, 'submitPickupSortingProduct'])->middleware(['auth', 'verified'])->name('submitSortingProduct');
Route::get('/pickupSortingProduct', [SortingController::class, 'getPickupSortingProduct'])->middleware(['auth', 'verified'])->name('pickupSortingProduct');
Route::get('/pickupSortingRange', [SortingController::class, 'getSortingProductRange'])->middleware(['auth', 'verified'])->name('getSortingRange');
Route::Post('/saveSortingProduct', [SortingController::class, 'saveSortingProduct'])->middleware(['auth', 'verified'])->name('saveSortingProduct');
Route::Post('/saveSortingEdits', [SortingController::class, 'saveSortingEdits'])->middleware(['auth', 'verified'])->name('saveSortingEdits');

require __DIR__.'/settings.php';
