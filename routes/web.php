<?php

use App\Http\Controllers\AppointmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AppointmentController::class, 'index'])->name('appointments.index');
Route::get('/availability', [AppointmentController::class, 'availability'])
    ->middleware('throttle:30,1')
    ->name('appointments.availability');
Route::post('/appointments', [AppointmentController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('appointments.store');
