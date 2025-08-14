<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController; // <-- Import

// Endpoint publik untuk login
Route::post('/login', [AuthController::class, 'login']);

// Endpoint yang dilindungi (memerlukan token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- TAMBAHKAN SEMUA ROUTE DI BAWAH INI ---
    Route::post('/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/check-out', [AttendanceController::class, 'checkOut']);
    Route::post('/submit-leave', [AttendanceController::class, 'submitLeave']);
    Route::get('/today-attendance', [AttendanceController::class, 'getTodayAttendance']);
});
    