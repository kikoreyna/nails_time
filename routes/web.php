<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\PaymentReviewController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TechnicianController;
use App\Http\Controllers\AppointmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AppointmentController::class, 'index'])->name('appointments.index');
Route::get('/availability', [AppointmentController::class, 'availability'])
    ->middleware('throttle:30,1')
    ->name('appointments.availability');
Route::post('/appointments', [AppointmentController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('appointments.store');

Route::middleware('signed')->group(function () {
    Route::get('/appointments/{appointment}/payment', [AppointmentController::class, 'payment'])
        ->name('appointments.payment');
    Route::post('/appointments/{appointment}/payment-proof', [AppointmentController::class, 'storeProof'])
        ->middleware('throttle:10,1')
        ->name('appointments.proof.store');
});

Route::prefix('admin')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'create'])->name('login');
        Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('admin.login.store');
    });

    Route::middleware(['auth', 'staff'])->name('admin.')->group(function () {
        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
        Route::post('/appointments/{appointment}/status', [CalendarController::class, 'updateStatus'])->name('appointments.status');
    });

    Route::middleware(['auth', 'admin'])->name('admin.')->group(function () {
        Route::resource('technicians', TechnicianController::class)->except('show');
        Route::resource('services', ServiceController::class)->except('show');
        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::put('/settings/schedule', [ScheduleController::class, 'update'])->name('schedule.update');
        Route::post('/settings/blocked-times', [ScheduleController::class, 'storeBlock'])->name('blocked-times.store');
        Route::delete('/settings/blocked-times/{blockedTime}', [ScheduleController::class, 'destroyBlock'])->name('blocked-times.destroy');
        Route::get('/appointments', [PaymentReviewController::class, 'index'])->name('appointments.index');
        Route::get('/payment-proofs/{proof}', [PaymentReviewController::class, 'proof'])->name('proofs.show');
        Route::post('/appointments/{appointment}/approve', [PaymentReviewController::class, 'approve'])->name('appointments.approve');
        Route::post('/appointments/{appointment}/reject', [PaymentReviewController::class, 'reject'])->name('appointments.reject');
    });
});
