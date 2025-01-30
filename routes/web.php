<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/acelle-connect', function () {
    return view('acelleConnect');
});

Route::get('/connect', [\App\Http\Controllers\AppController::class, 'connect']);