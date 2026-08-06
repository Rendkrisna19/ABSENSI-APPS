<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kost;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Xendit\Configuration;
use Carbon\Carbon; 
use Xendit\Invoice\InvoiceApi;

class TransactionApiController extends Controller
{
    public function __construct()
    {
        Configuration::setXenditKey(env('XENDIT_SECRET_KEY'));
    }

    // --- 1. LIST TRANSAKSI ---
    public function index()
    {
        $user = Auth::user();
        // Ambil data terbaru (descending) agar yang baru dipesan muncul di atas
        $transactions = Transaction::with('kost')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc') 
            ->get();

        return response()->json([
            'message' => 'List transaksi berhasil diambil',
            'data' => $transactions
        ], 200);
    }

    // --- 2. STORE: BOOKING BARU ---
    public function store(Request $request)
    {
        $request->validate([
            'kost_id' => 'required|exists:kosts,id',
            'duration' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'tenant_name' => 'nullable|string',
            'tenant_phone' => 'nullable|string',
            'tenant_type' => 'nullable|in:self,family,friend,partner',
        ]);

        $user = Auth::user();
        $kost = Kost::findOrFail($request->kost_id);

        if ($kost->available_rooms < 1) {
            return response()->json(['message' => 'Maaf, kamar sudah penuh.'], 400);
        }

        $totalPrice = $kost->price_per_month * $request->duration;
        $externalId = 'ORD-' . time() . '-' . $user->id;

        $isSelf = $request->tenant_type === 'self' || !$request->tenant_name;
        $tenantName = $isSelf ? $user->name : $request->tenant_name;
        $tenantPhone = $isSelf ? $user->phone : $request->tenant_phone;
        $tenantType = $request->tenant_type ?? 'self';

        // Hitung End Date
        $startDate = Carbon::parse($request->start_date);
        $endDate = $startDate->copy()->addMonths($request->duration);

        $apiInstance = new InvoiceApi();
        $createInvoiceRequest = new \Xendit\Invoice\CreateInvoiceRequest([
            'external_id' => $externalId,
            'amount' => $totalPrice,
            'payer_email' => $user->email,
            'description' => "Sewa Kost: " . $kost->name . " ($request->duration Bulan)",
            'invoice_duration' => 172800,
            'currency' => 'IDR',
            'success_redirect_url' => url('/api/payment/success'),
            'failure_redirect_url' => url('/api/payment/failed'),
        ]);

        try {
            $result = $apiInstance->createInvoice($createInvoiceRequest);

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'kost_id' => $kost->id,
                'start_date' => $request->start_date,
                'end_date' => $endDate, 
                'duration' => $request->duration,
                'total_price' => $totalPrice,
                'external_id' => $externalId,
                'payment_url' => $result['invoice_url'],
                'status' => 'PENDING',
                'rent_status' => 'UPCOMING',
                'tenant_name' => $tenantName,
                'tenant_phone' => $tenantPhone,
                'tenant_type' => $tenantType,
            ]);

            return response()->json([
                'message' => 'Invoice berhasil dibuat',
                'data' => $transaction,
                'payment_url' => $result['invoice_url']
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Xendit Error: ' . $e->getMessage()], 500);
        }
    }

    // --- 3. EXTEND: PERPANJANG SEWA ---
    public function extend(Request $request, $id)
    {
        $request->validate([
            'duration' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        $oldTransaction = Transaction::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $kost = Kost::findOrFail($oldTransaction->kost_id);

        $oldStartDate = Carbon::parse($oldTransaction->start_date);
        
        // Cek end_date lama
        if ($oldTransaction->end_date) {
             $newStartDate = Carbon::parse($oldTransaction->end_date);
        } else {
             $newStartDate = $oldStartDate->copy()->addMonths($oldTransaction->duration);
        }
        
        $newEndDate = $newStartDate->copy()->addMonths($request->duration);
        $totalPrice = $kost->price_per_month * $request->duration;
        $externalId = 'EXT-' . time() . '-' . $user->id;

        $apiInstance = new InvoiceApi();
        $createInvoiceRequest = new \Xendit\Invoice\CreateInvoiceRequest([
            'external_id' => $externalId,
            'amount' => $totalPrice,
            'payer_email' => $user->email,
            'description' => "Perpanjang Kost: " . $kost->name . " ($request->duration Bulan)",
            'invoice_duration' => 86400,
            'currency' => 'IDR',
            'success_redirect_url' => url('/api/payment/success'),
            'failure_redirect_url' => url('/api/payment/failed'),
        ]);

        try {
            $result = $apiInstance->createInvoice($createInvoiceRequest);

            $newTransaction = Transaction::create([
                'user_id' => $user->id,
                'kost_id' => $kost->id,
                'start_date' => $newStartDate->format('Y-m-d'),
                'end_date' => $newEndDate->format('Y-m-d'), 
                'duration' => $request->duration,
                'total_price' => $totalPrice,
                'external_id' => $externalId,
                'payment_url' => $result['invoice_url'],
                'status' => 'PENDING',
                'rent_status' => 'UPCOMING', 
                'tenant_name' => $oldTransaction->tenant_name,
                'tenant_phone' => $oldTransaction->tenant_phone,
                'tenant_type' => $oldTransaction->tenant_type,
            ]);

            return response()->json([
                'message' => 'Link perpanjangan berhasil dibuat',
                'data' => $newTransaction,
                'payment_url' => $result['invoice_url']
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal perpanjang: ' . $e->getMessage()], 500);
        }
    }

    // --- 4. HELPER UPDATE STATUS ---
    private function updateRentStatusIfStarted($transaction)
    {
        $startDate = Carbon::parse($transaction->start_date);
        
        if ($transaction->end_date) {
            $endDate = Carbon::parse($transaction->end_date);
        } else {
            $endDate = $startDate->copy()->addMonths($transaction->duration);
        }
        
        $now = now();

        // 1. UPCOMING -> ACTIVE
        if ($transaction->status == 'PAID' && $transaction->rent_status == 'UPCOMING') {
            if ($now->greaterThanOrEqualTo($startDate)) {
                $transaction->update(['rent_status' => 'ACTIVE']);
                $transaction->refresh();
            }
        }

        // 2. ACTIVE -> COMPLETED
        if ($transaction->rent_status == 'ACTIVE') {
            if ($now->greaterThan($endDate->endOfDay())) {
                $transaction->update(['rent_status' => 'COMPLETED']);
                $transaction->refresh();
            }
        }
        
        return $transaction;
    }

    // --- 5. CHECK DETAIL ---
    public function check($id)
    {
        $transaction = Transaction::findOrFail($id);
        $transaction = $this->updateRentStatusIfStarted($transaction);

        if ($transaction->status == 'PENDING') {
            try {
                $apiInstance = new InvoiceApi();
                $invoices = $apiInstance->getInvoices(null, $transaction->external_id);
                
                if (count($invoices) > 0) {
                    $xenditStatus = $invoices[0]['status'];

                    if ($xenditStatus == 'PAID' || $xenditStatus == 'SETTLED') {
                        DB::beginTransaction();
                        try {
                            $transaction->update(['status' => 'PAID', 'paid_at' => now()]);
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
                Log::error("Xendit Check Error: " . $e->getMessage());
            }
        }

        $transaction->load('kost');
        $transaction->refresh();

        return response()->json(['status' => $transaction->status, 'data' => $transaction]);
    }

    // --- 6. ACTIVE RENT ---
    public function activeRent()
    {
        $user = Auth::user();
        $transactions = Transaction::with('kost')
            ->where('user_id', $user->id)
            ->where('status', 'PAID')
            ->orderBy('start_date', 'desc') 
            ->get();
        
        $activeRent = null;
        $responseDetails = [];

        foreach ($transactions as $trx) {
            $trx = $this->updateRentStatusIfStarted($trx);

            if ($trx->rent_status == 'ACTIVE') {
                $activeRent = $trx;
                
                if ($trx->end_date) {
                    $end = Carbon::parse($trx->end_date);
                } else {
                    $start = Carbon::parse($trx->start_date);
                    $end = $start->copy()->addMonths($trx->duration);
                }
                
                $now = now();
                $daysRemaining = $now->diffInDays($end, false);
                $isDueSoon = $daysRemaining <= 7 && $daysRemaining >= 0;
                $isOverdue = $daysRemaining < 0;

                $responseDetails = [
                    'due_date' => $end->format('Y-m-d'),      
                    'due_date_formatted' => $end->format('d M Y'), 
                    'days_remaining' => (int)$daysRemaining,
                    'is_due_soon' => $isDueSoon,
                    'is_overdue' => $isOverdue,
                    'status_label' => $isOverdue ? "Lewat Jatuh Tempo" : ($isDueSoon ? "Segera Perpanjang" : "Aktif")
                ];
                break; 
            }
        }

        if ($activeRent) {
            $activeRent->setAttribute('rent_details', $responseDetails);
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

    // --- 7. STOP RENT (REFUND) ---
    public function stopRent(Request $request, $id)
    {
        $request->validate([
            'bank_name' => 'required|string',
            'account_number' => 'required|numeric',
            'account_name' => 'required|string',
        ]);

        $user = Auth::user();
        $transaction = Transaction::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        $startDate = Carbon::parse($transaction->start_date);
        
        if ($transaction->end_date) {
            $endDate = Carbon::parse($transaction->end_date);
        } else {
            $endDate = $startDate->copy()->addMonths($transaction->duration);
        }
        
        $stopDate = now();

        // Validasi: Tidak bisa refund jika sudah lewat masa sewa
        if ($stopDate->greaterThan($endDate->endOfDay())) {
             return response()->json(['message' => 'Masa sewa sudah habis, tidak bisa mengajukan refund.'], 400);
        }

        // Logic Refund
        if ($transaction->rent_status == 'UPCOMING' || $stopDate->lt($startDate)) {
            // Jika status UPCOMING atau belum mulai -> Hitung 0 bulan pakai
            $monthsUsed = 0;
        } else {
            // Jika sudah ACTIVE (lewat 1 detik pun) -> Hitung 1 bulan pakai (pembulatan ke atas)
            $monthsUsed = $startDate->diffInMonths($stopDate);
            if ($stopDate->day > $startDate->day) {
                $monthsUsed += 1; 
            }
            if ($monthsUsed < 1) $monthsUsed = 1; 
        }

        // Hitung nominal
        $pricePerMonth = $transaction->total_price / $transaction->duration;
        $costUsed = $pricePerMonth * $monthsUsed;
        $remainingValue = $transaction->total_price - $costUsed;
        
        $penalty = $transaction->total_price * 0.10;
        $refundAmount = $remainingValue - $penalty;
        
        if ($refundAmount < 0) $refundAmount = 0;

        DB::transaction(function () use ($transaction, $stopDate, $penalty, $refundAmount, $request) {
            $transaction->update([
                'rent_status' => 'STOPPED',
                'stopped_at' => $stopDate,
                'penalty_amount' => $penalty,
                'refund_amount' => $refundAmount,
                'refund_bank_name' => $request->bank_name,
                'refund_account_number' => $request->account_number,
                'refund_account_name' => $request->account_name,
            ]);

            $kost = Kost::lockForUpdate()->find($transaction->kost_id);
            $kost->increment('available_rooms');
        });

        return response()->json([
            'message' => 'Berhenti sewa berhasil diproses.',
            'data' => ['refund_amount' => $refundAmount]
        ]);
    }

    // --- 8. CALLBACK ---
    public function callback(Request $request)
    {
        Log::info('Xendit Callback Received:', $request->all());
        $xenditXCallbackToken = env('XENDIT_CALLBACK_TOKEN');
        $reqToken = $request->header('x-callback-token');

        if ($xenditXCallbackToken && $xenditXCallbackToken != $reqToken) {
            return response()->json(['message' => 'Invalid Token'], 403);
        }

        $data = $request->all();
        $status = $data['status'] ?? '';
        $externalId = $data['external_id'] ?? '';

        $transaction = Transaction::where('external_id', $externalId)->first();

        if (!$transaction) return response()->json(['message' => 'Transaction not found'], 404);
        if ($transaction->status == 'PAID') return response()->json(['message' => 'Transaction already paid'], 200);

        if ($status == 'PAID' || $status == 'SETTLED') {
            DB::beginTransaction();
            try {
                $transaction->update([
                    'status' => 'PAID',
                    'payment_method' => $data['payment_method'] ?? null,
                    'paid_at' => now()
                ]);

                $kost = Kost::lockForUpdate()->find($transaction->kost_id);
                if ($kost && $kost->available_rooms > 0) {
                    $kost->decrement('available_rooms');
                }
                
                $rentStatus = now()->greaterThanOrEqualTo($transaction->start_date) ? 'ACTIVE' : 'UPCOMING';
                $transaction->update(['rent_status' => $rentStatus]);

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

    // --- 9. SUCCESS PAGE ---
    public function success()
    {
        return view('payment_success');
    }
}