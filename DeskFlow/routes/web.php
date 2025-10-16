<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LayoutController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/layout', [LayoutController::class, 'index']);
Route::post('/layout/save', [LayoutController::class, 'save']);
Route::get('/layout/load', [LayoutController::class, 'load']);
