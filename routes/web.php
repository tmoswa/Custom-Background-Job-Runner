<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Catch-all route for SPA, excluding API routes
Route::get('/{any}', function () {
    return view('app');
})->where('any', '(?!api).*');//->middleware('auth:sanctum');
