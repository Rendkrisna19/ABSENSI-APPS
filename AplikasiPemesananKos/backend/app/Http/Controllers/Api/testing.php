<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kost;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Tambahkan ini untuk debugging
use Illuminate\Support\Facades\DB;   // Tambahkan untuk database transaction
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;

class TransactionApiController extends Controller
{
  public function __construct()
    {
        Configuration::setXenditKey(env('XENDIT_SECRET_KEY'));
    }

    public function index()
    {
        $user = Auth::user();
        $transactions = Transaction::with('kost')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'List transaksi berhasil diambil',
            'data' => $transactions
        ], 200);
    }

    // --- STORE: GABUNGAN LOGIKA XENDIT + FITUR ORANG LAIN ---
    public function store(Request $request)
    {
        // 1. Validasi Input (Termasuk data penyewa baru)
        $request->validate([
            'kost_id' => 'required|exists:kosts,id',
            'duration' => 'required|integer|min:1',
            'start_date' => 'required|date',
            // Validasi tambahan untuk fitur booking orang lain
            'tenant_name' => 'nullable|string',
            'tenant_phone' => 'nullable|string',
            'tenant_type' => 'nullable|in:self,family,friend,partner',
        ]);

        $user = Auth::user();
        $kost = Kost::findOrFail($request->kost_id);

        // 2. Cek Ketersediaan
        if ($kost->available_rooms < 1) {
            return response()->json(['message' => 'Maaf, kamar sudah penuh.'], 400);
        }

        // 3. Hitung Harga
        $totalPrice = $kost->price_per_month * $request->duration;
        $externalId = 'ORD-' . time() . '-' . $user->id;

        // 4. Tentukan Data Penghuni (Diri sendiri atau Orang lain?)
        // Jika tenant_type 'self' atau kosong, pakai data user yang login
        $isSelf = $request->tenant_type === 'self' || !$request->tenant_name;
        
        $tenantName = $isSelf ? $user->name : $request->tenant_name;
        $tenantPhone = $isSelf ? $user->phone : $request->tenant_phone; // Pastikan user punya field phone
        $tenantType = $request->tenant_type ?? 'self';

        // 5. Buat Invoice Xendit (Kode Asli Kamu)
        $apiInstance = new InvoiceApi();
        $createInvoiceRequest = new \Xendit\Invoice\CreateInvoiceRequest([
            'external_id' => $externalId,
            'amount' => $totalPrice,
            'payer_email' => $user->email,
            'description' => "Sewa Kost: " . $kost->name . " ($request->duration Bulan) untuk $tenantName",
            'invoice_duration' => 172800, 
            'currency' => 'IDR',
            'reminder_time' => 1,
            'success_redirect_url' => url('/api/payment/success'), 
            'failure_redirect_url' => url('/api/payment/failed'),
        ]);

        try {
            // Panggil Xendit
            $result = $apiInstance->createInvoice($createInvoiceRequest);

            // 6. Simpan ke Database (Gabungan Field Lama + Baru)
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'kost_id' => $kost->id,
                'start_date' => $request->start_date,
                'duration' => $request->duration,
                'total_price' => $totalPrice,
                'external_id' => $externalId,
                'payment_url' => $result['invoice_url'],
                'status' => 'PENDING',
                
                // FIELD BARU (Pastikan sudah di migrate ke tabel transactions)
                'tenant_name' => $tenantName,
                'tenant_phone' => $tenantPhone,
                'tenant_type' => $tenantType,
            ]);

            return response()->json([
                'message' => 'Invoice berhasil dibuat',
                'data' => $transaction,
                'payment_url' => $result['invoice_url'] // URL Xendit dikembalikan ke Flutter
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Xendit Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // 7. Check Status (Logic Kamu Tetap)
    private function updateRentStatusIfStarted($transaction)
    {
        // Cek jika status pembayaran PAID (Lunas) tapi sewa masih UPCOMING (Belum mulai)
        if ($transaction->status == 'PAID' && $transaction->rent_status == 'UPCOMING') {
            // Cek apakah hari ini >= tanggal mulai sewa
            if (now()->greaterThanOrEqualTo($transaction->start_date)) {
                $transaction->update(['rent_status' => 'ACTIVE']);
                $transaction->refresh(); // Reload data baru
            }
        }
        return $transaction;
    }

    // 1. DETAIL TRANSAKSI (Function CHECK)
    public function check($id)
    {
        $transaction = Transaction::findOrFail($id);

        // --- TAMBAHAN: Cek Update Status Otomatis Disini ---
        $transaction = $this->updateRentStatusIfStarted($transaction);

        // Logic cek status Xendit (tetap sama seperti sebelumnya)
        if ($transaction->status == 'PENDING') {
            try {
                $apiInstance = new InvoiceApi();
                $invoices = $apiInstance->getInvoices(null, $transaction->external_id);
                
                if (count($invoices) > 0) {
                    $xenditStatus = $invoices[0]['status'];

                    if ($xenditStatus == 'PAID' || $xenditStatus == 'SETTLED') {
                        DB::beginTransaction();
                        try {
                            $transaction->update([
                                'status' => 'PAID',
                                'paid_at' => now()
                            ]);

                            // Set rent status awal (UPCOMING atau ACTIVE tergantung tanggal)
                            $rentStatus = now()->greaterThanOrEqualTo($transaction->start_date) ? 'ACTIVE' : 'UPCOMING';
                            $transaction->update(['rent_status' => $rentStatus]);

                            $kost = Kost::lockForUpdate()->find($transaction->kost_id);
                            if ($kost->available_rooms > 0) {
                                $kost->decrement('available_rooms');
                            }
                            
                            DB::commit();
                        } catch (\Exception $e) {
                            DB::rollBack();
                        }
                    } elseif ($xenditStatus == 'EXPIRED') {
                        $transaction->update(['status' => 'EXPIRED']);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Gagal cek Xendit manual: " . $e->getMessage());
            }
        }

        // Load data relasi kost agar tidak error di Flutter
        $transaction->load('kost');
        $transaction->refresh(); // Refresh lagi untuk memastikan data terbaru

        return response()->json([
            'status' => $transaction->status,
            'data' => $transaction
        ]);
    }

 
    // 2. Webhook / Callback (PERBAIKAN UTAMA DISINI)
    public function callback(Request $request)
    {
        // 1. Log data yang masuk (Cek di storage/logs/laravel.log jika callback tidak jalan)
        Log::info('Xendit Callback Received:', $request->all());

        // 2. Verifikasi Token Xendit (Wajib diisi di .env sebagai XENDIT_CALLBACK_TOKEN)
        // Ini memastikan request benar-benar dari Xendit
        $xenditXCallbackToken = env('XENDIT_CALLBACK_TOKEN');
        $reqToken = $request->header('x-callback-token');

        if ($xenditXCallbackToken && $xenditXCallbackToken != $reqToken) {
            return response()->json(['message' => 'Invalid Token'], 403);
        }

        // 3. Ambil data
        $data = $request->all();
        $status = $data['status'] ?? '';
        $externalId = $data['external_id'] ?? '';

        // 4. Cari Transaksi
        $transaction = Transaction::where('external_id', $externalId)->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // 5. Cek apakah status transaksinya SUDAH PAID sebelumnya?
        // (Untuk menghindari pengurangan stok 2x jika Xendit mengirim callback berulang)
        if ($transaction->status == 'PAID') {
            return response()->json(['message' => 'Transaction already paid'], 200);
        }

        // 6. Proses Update Status & Stok
        if ($status == 'PAID' || $status == 'SETTLED') {
            
            // Gunakan DB Transaction agar data konsisten
            DB::beginTransaction();
            try {
                // Update Status Transaksi
                $transaction->update([
                    'status' => 'PAID',
                    'payment_method' => $data['payment_method'] ?? null, // Opsional: simpan metode bayar
                    'paid_at' => now() // Opsional: simpan waktu bayar
                ]);

                // Kurangi Stok Kamar
                $kost = Kost::lockForUpdate()->find($transaction->kost_id); // Lock row agar aman
                
                if ($kost && $kost->available_rooms > 0) {
                    $kost->decrement('available_rooms');
                    Log::info("Stok berkurang untuk Kost ID: {$kost->id}");
                } else {
                    // Opsional: Handle jika tiba-tiba stok habis saat user sedang bayar
                    Log::warning("Stok habis saat pembayaran diterima: {$transaction->id}");
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error processing callback: " . $e->getMessage());
                return response()->json(['message' => 'Error updating data'], 500);
            }
        } elseif ($status == 'EXPIRED') {
            $transaction->update(['status' => 'EXPIRED']);
        }

        return response()->json(['message' => 'Callback success'], 200);
    }

    public function success()
    {
        return view('payment_success');
    }

    // Method untuk berhenti sewa & hitung refund
   public function stopRent(Request $request, $id)
    {
        $request->validate([
            'bank_name' => 'required|string',
            'account_number' => 'required|numeric',
            'account_name' => 'required|string',
        ]);

        $user = Auth::user();
        $transaction = Transaction::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        // Cek Status
        if ($transaction->rent_status != 'ACTIVE' && $transaction->rent_status != 'UPCOMING') {
            return response()->json(['message' => 'Tidak bisa membatalkan transaksi ini.'], 400);
        }

        // 1. Hitung Durasi Terpakai
        $startDate = \Carbon\Carbon::parse($transaction->start_date);
        $stopDate = now();
        
        // Jika cancel sebelum mulai (UPCOMING), terpakai 0 bulan
        if ($transaction->rent_status == 'UPCOMING' || $stopDate->lt($startDate)) {
            $monthsUsed = 0;
        } else {
            $monthsUsed = $startDate->diffInMonths($stopDate) + 1; 
        }

        if ($monthsUsed >= $transaction->duration) {
             return response()->json(['message' => 'Sewa sudah habis, tidak bisa refund.'], 400);
        }

        // 2. Hitung Nominal
        $pricePerMonth = $transaction->total_price / $transaction->duration;
        $costUsed = $pricePerMonth * $monthsUsed;
        $remainingValue = $transaction->total_price - $costUsed;
        
        // Denda 10% dari Total Kontrak
        $penalty = $transaction->total_price * 0.10;
        $refundAmount = $remainingValue - $penalty;
        
        if ($refundAmount < 0) $refundAmount = 0;

        // 3. Simpan ke DB
        DB::transaction(function () use ($transaction, $stopDate, $penalty, $refundAmount, $request) {
            $transaction->update([
                'rent_status' => 'STOPPED', // Atau STOP_REQUESTED jika mau fitur approve admin
                'stopped_at' => $stopDate,
                'penalty_amount' => $penalty,
                'refund_amount' => $refundAmount,
                // Simpan Data Bank
                'refund_bank_name' => $request->bank_name,
                'refund_account_number' => $request->account_number,
                'refund_account_name' => $request->account_name,
            ]);

            // Kembalikan Stok Kamar
            $kost = Kost::lockForUpdate()->find($transaction->kost_id);
            $kost->increment('available_rooms');
        });

        return response()->json([
            'message' => 'Berhenti sewa berhasil. Admin akan memproses refund ke rekening Anda.',
            'data' => [
                'refund_amount' => $refundAmount,
                'bank_dest' => $request->bank_name . ' - ' . $request->account_number
            ]
        ]);
    }


    public function activeRent()
    {
        $user = Auth::user();

        // Cari transaksi potensial (PAID)
        $transactions = Transaction::with('kost')
            ->where('user_id', $user->id)
            ->where('status', 'PAID')
            ->orderBy('start_date', 'desc') // Ambil yang paling baru
            ->get();
        
        $activeRent = null;

        foreach ($transactions as $trx) {
            // Jalankan update otomatis untuk setiap transaksi yang ditemukan
            $trx = $this->updateRentStatusIfStarted($trx);

            // Jika statusnya ACTIVE, atau UPCOMING tapi start_date <= hari ini
            if ($trx->rent_status == 'ACTIVE') {
                $activeRent = $trx;
                break; // Ketemu satu yang aktif, stop looping
            }
        }

        if ($activeRent) {
            return response()->json([
                'has_active_rent' => true,
                'data' => $activeRent
            ]);
        }

        return response()->json([
            'has_active_rent' => false,
            'message' => 'Tidak ada kos aktif'
        ]);
    }
} 