<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\KostApiController;
use App\Http\Controllers\Api\TransactionApiController;
use App\Http\Controllers\Api\BookingApiController;
use App\Http\Controllers\Api\ChatApiController;

// === PUBLIC ROUTES (Tanpa Login) ===

// Auth
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

// Data Kost
Route::get('/kosts', [KostApiController::class, 'index']);
Route::get('/kosts/{id}', [KostApiController::class, 'show']);

// Callback Xendit (Wajib Public agar bisa ditembak Xendit)
Route::post('/payment/callback', [TransactionApiController::class, 'callback']);
Route::get('/payment/success', [TransactionApiController::class, 'success']);


// === PROTECTED ROUTES (Harus Login) ===
Route::middleware('auth:sanctum')->group(function () {
    
    // awalan
    Route::get('/transactions', [App\Http\Controllers\Api\TransactionApiController::class, 'index']);

    // User Info
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Transaksi
    Route::post('/transactions', [TransactionApiController::class, 'store']); // Bikin pesanan
    Route::get('/transactions', [TransactionApiController::class, 'index']); // List pesanan
    Route::get('/transactions/{id}', [TransactionApiController::class, 'show']); // Detail
    
    // Route Penting untuk Cek Status Manual dari Flutter
    Route::get('/transactions/{id}/check', [TransactionApiController::class, 'checkStatus']);
    Route::post('/payment/callback', [App\Http\Controllers\Api\TransactionApiController::class, 'callback']);
    Route::get('/transactions/{id}/check', [TransactionApiController::class, 'check']);
    //routes untuk booking 
    Route::post('/bookings', [BookingApiController::class, 'store']); // Pesan (buat sendiri/orang lain)
    Route::get('/bookings/active', [BookingApiController::class, 'myActiveBookings']); // Cek saya aktif dimana
    Route::post('/bookings/{id}/stop', [BookingApiController::class, 'stopEarly']); // Berhenti & Refund
    Route::post('/bookings/{id}/extend', [BookingApiController::class, 'extend']); // Perpanjang
    //routes chatting 
   // --- FITUR CHAT ---
    
    // 1. Ambil daftar kontak/inbox (Halaman depan chat)
    Route::get('/chat-list', [ChatApiController::class, 'getChatList']);
    
    // 2. Kirim pesan
    Route::post('/chats/send', [ChatApiController::class, 'sendMessage']);
    
    // 3. Ambil detail percakapan dengan user tertentu
    // {userId} adalah ID lawan bicara
    Route::get('/chats/{userId}', [ChatApiController::class, 'getMessages']);


    //cek status kos apakah sudah aktif atau belum 
    Route::get('/transactions/active-rent', [App\Http\Controllers\Api\TransactionApiController::class, 'activeRent']);
    Route::get('/transactions/active-rent', [TransactionApiController::class, 'activeRent']);

// Route untuk perpanjang sewa (Bayar lagi via Xendit)
Route::post('/transactions/{id}/extend', [TransactionApiController::class, 'extend']);

Route::post('/transactions/{id}/stop', [TransactionApiController::class, 'stopRent']);

    // 2. Route untuk Perpanjang Sewa (Extend) - Agar fitur perpanjang nanti tidak error juga
    Route::post('/transactions/{id}/extend', [TransactionApiController::class, 'extend']);
});