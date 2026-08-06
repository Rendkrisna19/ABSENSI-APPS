<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Kost;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class BookingApiController extends Controller
{
    // 1. BUAT PESANAN (Bisa set untuk diri sendiri atau orang lain)
    public function store(Request $request)
    {
        $request->validate([
            'kost_id' => 'required|exists:kosts,id',
            'start_date' => 'required|date',
            'duration_months' => 'required|integer|min:1',
            // Opsi: booking untuk orang lain
            'tenant_name' => 'nullable|string', 
            'tenant_phone' => 'nullable|string',
            'tenant_type' => 'nullable|in:self,family,friend,partner', 
        ]);

        $kost = Kost::findOrFail($request->kost_id);
        $user = Auth::user(); // User yang login (customer)

        // Hitung total harga
        $totalPrice = $kost->price_per_month * $request->duration_months;

        // Tentukan siapa penghuninya
        $isSelf = $request->tenant_type === 'self' || !$request->tenant_name;
        
        $booking = Booking::create([
            'user_id' => $user->id,
            'kost_id' => $kost->id,
            'start_date' => $request->start_date,
            'duration_months' => $request->duration_months,
            'end_date' => Carbon::parse($request->start_date)->addMonths($request->duration_months),
            'total_price' => $totalPrice,
            'status' => 'active',
            
            // Logic Booking untuk orang lain
            'tenant_name' => $isSelf ? $user->name : $request->tenant_name,
            'tenant_phone' => $isSelf ? $user->phone : $request->tenant_phone,
            'tenant_type' => $request->tenant_type ?? 'self',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil dibuat',
            'data' => $booking
        ]);
    }

    // 2. CEK STATUS AKTIF (Untuk melihat saya aktif di kos mana)
    public function myActiveBookings()
    {
        $user = Auth::user();
        
        $bookings = Booking::with('kost')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    // 3. FITUR BERHENTI & REFUND (STOP EARLY)
    public function stopEarly(Request $request, $id)
    {
        $booking = Booking::where('user_id', Auth::id())->findOrFail($id);

        if ($booking->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Booking tidak aktif'], 400);
        }

        // Tanggal berhenti hari ini
        $stopDate = Carbon::now();
        $startDate = Carbon::parse($booking->start_date);

        // Hitung berapa bulan sudah berjalan (pembulatan ke atas, misal 1 hari dianggap 1 bulan)
        // Atau gunakan floatDiffInMonths untuk lebih presisi, di sini kita pakai ceil (pembulatan bulan berjalan)
        $monthsUsed = $startDate->diffInMonths($stopDate);
        if ($monthsUsed < 1) $monthsUsed = 1; // Minimal hitung 1 bulan pakai

        // Validasi jika sudah lewat durasi booking
        if ($monthsUsed >= $booking->duration_months) {
            // Ini harusnya selesai normal, bukan stop early refund
            return response()->json(['success' => false, 'message' => 'Masa sewa sudah hampir habis, tidak bisa refund.'], 400);
        }

        // LOGIKA PERHITUNGAN UANG
        // Total awal: 20 Juta (12 Bulan)
        // Berhenti di bulan ke-6
        // Harga per bulan booking awal = 20jt / 12 = 1.66jt
        
        $pricePerMonth = $booking->total_price / $booking->duration_months;
        $costUsed = $pricePerMonth * $monthsUsed; // Biaya untuk masa yang sudah dipakai
        $remainingValue = $booking->total_price - $costUsed; // Sisa uang murni

        // Denda 10% dari TOTAL HARGA (Sesuai request: "10% dari total Harga")
        $penalty = $booking->total_price * 0.10; 

        // Hitung Refund
        $refundAmount = $remainingValue - $penalty;

        // Jika hasil minus (denda lebih besar dari sisa), maka refund 0
        if ($refundAmount < 0) {
            $refundAmount = 0;
        }

        // Update Database
        $booking->update([
            'status' => 'stopped',
            'stopped_at' => $stopDate,
            'penalty_amount' => $penalty,
            'refund_amount' => $refundAmount
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhenti sewa berhasil diproses.',
            'data' => [
                'booking_id' => $booking->id,
                'total_initial_price' => $booking->total_price,
                'months_used' => $monthsUsed,
                'cost_for_used_time' => $costUsed,
                'penalty_fee' => $penalty, // Masuk ke admin
                'refund_to_user' => $refundAmount // Kembali ke user
            ]
        ]);
    }

    // 4. PERPANJANG KOS (Extend)
    public function extend(Request $request, $id)
    {
        $request->validate(['additional_months' => 'required|integer|min:1']);
        
        $booking = Booking::where('user_id', Auth::id())->findOrFail($id);
        $kost = Kost::findOrFail($booking->kost_id);

        if ($booking->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Hanya booking aktif yang bisa diperpanjang'], 400);
        }

        $addMonths = $request->additional_months;
        $addPrice = $kost->price_per_month * $addMonths;

        // Update booking
        $newEndDate = Carbon::parse($booking->end_date)->addMonths($addMonths);
        
        $booking->update([
            'duration_months' => $booking->duration_months + $addMonths,
            'end_date' => $newEndDate,
            'total_price' => $booking->total_price + $addPrice, // Update total harga akumulasi
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Perpanjangan berhasil',
            'data' => $booking
        ]);
    }
}