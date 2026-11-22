<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\OfficeLocationController;
use App\Http\Controllers\FaceRegistrationController;
use App\Http\Controllers\ProfileController;

Route::get('/', function () {
    return redirect()->route('login');
});

require __DIR__.'/auth.php';

// Authenticated Routes for Employee & All Users
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        if (auth()->user()->role === 'owner') {
            return redirect()->route('owner.dashboard');
        }
        return redirect()->route('attendance.index');
    })->name('dashboard');

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/checkin', [AttendanceController::class, 'checkIn'])->name('attendance.checkin');
    Route::post('/attendance/checkout', [AttendanceController::class, 'checkOut'])->name('attendance.checkout');

    // Pengajuan Izin / Cuti Karyawan
    Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');
    Route::delete('/leave/{id}/cancel', [LeaveController::class, 'cancel'])->name('leave.cancel');

    // Profil Pengguna (Karyawan & Owner)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Fitur Pendaftaran Biometrik Wajah
    Route::get('/face-registration', [FaceRegistrationController::class, 'index'])->name('face.registration');
    Route::post('/face-registration', [FaceRegistrationController::class, 'store'])->name('face.store');
    Route::get('/face-descriptor', [FaceRegistrationController::class, 'getDescriptor'])->name('face.descriptor');

    // API Publik terotentikasi untuk cek lokasi kantor aktif
    Route::get('/api/office-locations', [OfficeLocationController::class, 'getActiveLocations'])->name('api.office.locations');
});

// Authenticated Routes for Owner
Route::middleware(['auth'])->group(function () {
    Route::get('/owner/dashboard', [OwnerController::class, 'dashboard'])->name('owner.dashboard');
    Route::post('/owner/set-alfa', [OwnerController::class, 'setAlfa'])->name('owner.setAlfa');
    Route::delete('/owner/user/{user}', [OwnerController::class, 'destroy'])->name('owner.user.destroy');

    // Manajemen Akun Karyawan oleh Owner
    Route::post('/owner/employee', [OwnerController::class, 'storeEmployee'])->name('owner.employee.store');
    Route::put('/owner/employee/{user}', [OwnerController::class, 'updateEmployee'])->name('owner.employee.update');

    // Persetujuan Izin / Cuti oleh Owner
    Route::post('/owner/leave/{id}/status', [LeaveController::class, 'updateStatus'])->name('owner.leave.status');

    // Tarik / Export Data Absensi per Periode ke Excel & Laporan Website
    Route::get('/owner/reports', [OwnerController::class, 'reports'])->name('owner.reports');
    Route::get('/owner/attendance/export', [OwnerController::class, 'exportAttendance'])->name('owner.attendance.export');

    // Rute Kalender Detail Bulanan Karyawan
    Route::get('/owner/employee/{id}/attendance', [OwnerController::class, 'employeeDetail'])
        ->name('owner.employee.detail');

    // Manajemen Lokasi Kerja
    Route::get('/owner/office-locations', [OfficeLocationController::class, 'index'])->name('owner.office.locations');
    Route::post('/owner/office-locations/toggle', [OfficeLocationController::class, 'toggleRequirement'])->name('owner.office.toggle');
    Route::post('/owner/office-locations', [OfficeLocationController::class, 'store'])->name('owner.office.store');
    Route::put('/owner/office-locations/{location}', [OfficeLocationController::class, 'update'])->name('owner.office.update');
    Route::delete('/owner/office-locations/{location}', [OfficeLocationController::class, 'destroy'])->name('owner.office.destroy');
});
