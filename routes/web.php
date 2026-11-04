<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\OwnerController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Jika menggunakan Laravel Auth / Breeze bawaan, rute login biasanya di-load dari auth.php atau langsung
require __DIR__.'/auth.php';

// Authenticated Routes for Employee / Staff
Route::middleware(['auth'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/checkin', [AttendanceController::class, 'checkIn'])->name('attendance.checkin');
    Route::post('/attendance/checkout', [AttendanceController::class, 'checkOut'])->name('attendance.checkout');

    Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');
});

// Authenticated Routes for Owner
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        if (auth()->user()->role === 'owner') {
            return redirect()->route('owner.dashboard');
        }
        return redirect()->route('attendance.index');
    })->name('dashboard');

    // Rute owner dan lainnya...
    Route::get('/owner/dashboard', [OwnerController::class, 'dashboard'])->name('owner.dashboard');
    Route::get('/owner/dashboard', [OwnerController::class, 'dashboard'])->name('owner.dashboard');
    Route::post('/owner/set-alfa', [OwnerController::class, 'setAlfa'])->name('owner.setAlfa');
    Route::delete('/owner/user/{user}', [OwnerController::class, 'destroy'])->name('owner.user.destroy');
    
    // Rute Kalender Detail Bulanan Karyawan
    Route::get('/owner/employee/{id}/attendance', [OwnerController::class, 'employeeDetail'])
        ->name('owner.employee.detail');
});