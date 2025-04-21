<?php

use App\Http\Controllers\Api\JobController;
use Illuminate\Support\Facades\Route;

//Route::middleware('auth:sanctum')->group(function () {
    Route::get('/jobs', [JobController::class, 'index'])->name('api.jobs.index');
    Route::get('/jobs/logs', [JobController::class, 'logs'])->name('api.jobs.logs');
    Route::delete('/jobs/{job}', [JobController::class, 'cancel'])->name('api.jobs.cancel');
//});
