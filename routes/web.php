<?php

use App\Http\Controllers\AssetSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'asset-search',
        'search' => url('/search?q=hiring'),
    ]);
});

Route::get('/search', AssetSearchController::class);
