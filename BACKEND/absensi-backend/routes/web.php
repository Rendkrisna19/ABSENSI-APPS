<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\KaryawanController;
use App\Http\Controllers\Admin\WorkHourController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\AttendanceReportController;
use App\Http\Controllers\Admin\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
Route::get('/dashboard', [DashboardController::class, 'index'],)->name('dashboard'); // <-- koma ekstra
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('karyawan', KaryawanController::class);
    Route::get('pengaturan-jam-kerja', [WorkHourController::class, 'index'])->name('work-hours.index');
    Route::put('pengaturan-jam-kerja/{workHour}', [WorkHourController::class, 'update'])->name('work-hours.update');
    Route::resource('lokasi', LocationController::class)->names('locations');
     Route::get('laporan-absensi', [AttendanceReportController::class, 'index'])->name('reports.attendance');
});

require __DIR__.'/auth.php';
