<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\KostController;
use App\Http\Controllers\Admin\UserController; 
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\ChatController;
Route::get('/', function () {
    return view('welcome'); 
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin Routes (Protected by 'auth' and 'admin' middleware)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/dashboard', [KostController::class, 'dashboard'])->name('dashboard');
    
    // CRUD Kost
    Route::get('/kost', [KostController::class, 'index'])->name('kost.index');
    Route::get('/kost/create', [KostController::class, 'create'])->name('kost.create');
    Route::post('/kost', [KostController::class, 'store'])->name('kost.store');
    Route::get('/kost/{id}/edit', [KostController::class, 'edit'])->name('kost.edit');
    Route::put('/kost/{id}', [KostController::class, 'update'])->name('kost.update');
    Route::delete('/kost/{id}', [KostController::class, 'destroy'])->name('kost.destroy');

    //crud user
    Route::get('/user', [UserController::class, 'index'])->name('user.index');
    Route::get('/user/create', [UserController::class, 'create'])->name('user.create');
    Route::post('/user', [UserController::class, 'store'])->name('user.store');
    Route::get('/user/{id}/edit', [UserController::class, 'edit'])->name('user.edit');
    Route::put('/user/{id}', [UserController::class, 'update'])->name('user.update');
    Route::delete('/user/{id}', [UserController::class, 'destroy'])->name('user.destroy');

    // Route Transaksi
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transaction.index');
    Route::put('/transactions/{id}', [TransactionController::class, 'update'])->name('transaction.update');

    //routes refund 
    Route::get('/refunds', [RefundController::class, 'index'])->name('refund.index');
    Route::put('/refunds/{id}/complete', [RefundController::class, 'markAsRefunded'])->name('refund.complete');
    Route::get('/refunds/history', [RefundController::class, 'history'])->name('refund.history');
    // CHAT ADMIN
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/contacts', [ChatController::class, 'getContacts'])->name('chat.contacts');
    Route::get('/chat/conversation/{userId}', [ChatController::class, 'getConversation'])->name('chat.conversation');
    Route::post('/chat/send', [ChatController::class, 'sendMessage'])->name('chat.send');   
    
});