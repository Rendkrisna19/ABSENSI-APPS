<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Kost; // Jangan lupa import model Kost
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index()
    {
        // Ambil data transaksi beserta User dan Kost, urutkan dari terbaru
        $transactions = Transaction::with(['user', 'kost'])->latest()->get();
        return view('admin.transaction.index', compact('transactions'));
    }
    
    // Admin update status manual
    public function update(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);
        $oldStatus = $transaction->status;
        $newStatus = $request->status;

        // Validasi stok jika Admin mengubah status ke PAID secara manual
        if ($newStatus == 'PAID' && $oldStatus != 'PAID') {
            $kost = Kost::find($transaction->kost_id);
            
            if ($kost->available_rooms < 1) {
                return back()->with('error', 'Gagal update ke PAID! Sisa kamar habis.');
            }

            // Kurangi stok
            $kost->decrement('available_rooms');
        }

        // Jika status dibatalkan/failed dari posisi PAID (misal refund manual tanpa sistem), 
        // idealnya stok dikembalikan. (Opsional, aktifkan jika perlu)
        /*
        if (($newStatus == 'CANCELED' || $newStatus == 'FAILED') && $oldStatus == 'PAID') {
             $kost = Kost::find($transaction->kost_id);
             $kost->increment('available_rooms');
        }
        */

        $transaction->update([
            'status' => $newStatus
        ]);
        
        return back()->with('success', 'Status transaksi berhasil diperbarui.');
    }
}