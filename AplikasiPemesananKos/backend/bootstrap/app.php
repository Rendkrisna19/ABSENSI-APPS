<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\IsAdmin; // Pastikan class ini di-import

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',       // Route untuk Web Admin
        api: __DIR__ . '/../routes/api.php',       // Route untuk API Mobile
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        // 1. Konfigurasi API (Sanctum)
        // Ditaruh di sini agar hanya jalan saat akses API, tidak memberatkan Web Admin
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // 2. Daftarkan Alias Middleware
        // Kita gunakan nama 'admin' agar sesuai dengan route: middleware(['auth', 'admin'])
        $middleware->alias([
            'admin' => IsAdmin::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'api/payment/callback', // Route yang akan kita buat
        ]);

        // Optional: Jika nanti ingin mengatur exclude CSRF untuk route tertentu
        // $middleware->validateCsrfTokens(except: [
        //     'stripe/*',
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
