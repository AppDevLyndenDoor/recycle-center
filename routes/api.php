<?php

use App\Http\Controllers\EntryController;
use App\Http\Controllers\SortingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->get('/user', function (Request $request) {
        return $request->user();
    });

Route::middleware('auth:sanctum', 'anonymous.permission')->group(function () {



});

require __DIR__.'/settings.php';
