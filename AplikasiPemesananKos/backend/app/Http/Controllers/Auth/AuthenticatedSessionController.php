<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response; // Import Response
use Illuminate\Http\RedirectResponse; // Import RedirectResponse
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route; // Import Route

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // 1. Lakukan proses otentikasi seperti biasa
        $request->authenticate();

        // 2. Regenerate session
        $request->session()->regenerate();

        // --- INI ADALAH PERBAIKANNYA ---
        // 3. Cek role user yang baru saja login
        $user = $request->user();

        if ($user->role === 'admin') {
            // Jika dia admin, arahkan ke dashboard admin
            return redirect()->route('admin.dashboard'); // Menggunakan nama route
        }

        // 4. Jika bukan admin (dia 'user' biasa), arahkan ke dashboard user
        //    (atau ke halaman yang dituju sebelumnya jika ada)
        return redirect()->intended(Route::has('dashboard') ? route('dashboard') : '/dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}