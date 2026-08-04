<?php

use App\Http\Controllers\AssetSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/search', AssetSearchController::class);
