<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    // Menampilkan daftar pengajuan refund
    public function index()
    {
        // Ambil transaksi yang status sewanya 'STOPPED' (Berhenti & Minta Refund)
        // Kita juga bisa tambahkan status khusus misal 'REFUND_PENDING' jika mau lebih detail,
        // tapi 'STOPPED' dengan 'refund_amount > 0' sudah cukup.
        $refunds = Transaction::with(['user', 'kost'])
            ->where('rent_status', 'STOPPED')
            ->latest('stopped_at')
            ->get();

        return view('admin.refund.index', compact('refunds'));
    }

    // Menandai refund sudah ditransfer (Selesai)
    // Opsional: Kita bisa ubah status jadi 'REFUNDED' atau biarkan 'STOPPED' tapi kasih flag 'is_refunded'
    // Untuk simpelnya, kita anggap kalau admin sudah klik, berarti beres.
    // Disini saya akan mengubah status menjadi 'COMPLETED' (Selesai sepenuhnya) atau tetap 'STOPPED' dengan flash message.
    
    public function markAsRefunded($id)
    {
        $transaction = Transaction::findOrFail($id);
        
        // Opsi A: Ubah status jadi COMPLETED (agar hilang dari list refund aktif)
        // Opsi B: Tambah kolom 'refund_status' di database.
        // Opsi C (Paling Simpel): Biarkan di list tapi kasih tanda.
        
        // Kita pilih Opsi A agar list refund bersih:
        // Namun, agar history refund tetap ada, sebaiknya kita buat status baru 'REFUNDED'
        // Pastikan kolom status muat string panjang.
        
        $transaction->update([
            'rent_status' => 'REFUNDED' 
        ]);

        return back()->with('success', 'Refund berhasil ditandai selesai.');
    }


    //funtion untuk cetak laporan refund 
    public function history()
    {
        // Ambil data yang statusnya sudah selesai (REFUNDED)
        $refunds = Transaction::with(['user', 'kost'])
            ->where('rent_status', 'REFUNDED') 
            ->orderBy('updated_at', 'desc') // Urutkan dari yang baru selesai
            ->get();

        return view('admin.refund.history', compact('refunds'));
    } 
    
}