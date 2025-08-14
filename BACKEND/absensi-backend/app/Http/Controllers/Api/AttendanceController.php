<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\WorkHour;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Endpoint untuk Check-in
     */
    public function checkIn(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = $request->user();
        $today = Carbon::today();

        // 1. Cek apakah sudah ada absensi hari ini
        $existingAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->first();

        if ($existingAttendance) {
            return response()->json(['message' => 'Anda sudah melakukan absensi hari ini.'], 422);
        }

        // 2. Validasi Lokasi
        $officeLocation = Location::first(); // Asumsi hanya ada 1 lokasi kantor
        if (!$officeLocation) {
            return response()->json(['message' => 'Lokasi kantor belum diatur oleh admin.'], 422);
        }

        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $officeLocation->latitude,
            $officeLocation->longitude
        );

        if ($distance > $officeLocation->radius) {
            return response()->json(['message' => 'Anda berada di luar jangkauan area kantor.'], 422);
        }

        // 3. Tentukan Status (Hadir / Terlambat)
        $workHour = WorkHour::first(); // Ambil jam kerja standar
        $checkInTime = Carbon::now();
        $lateTime = Carbon::parse($workHour->check_in_time);

        $status = $checkInTime->isAfter($lateTime) ? 'Terlambat' : 'Hadir';

        // 4. Simpan data absensi
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'check_in_time' => $checkInTime,
            'check_in_latitude' => $request->latitude,
            'check_in_longitude' => $request->longitude,
            'status' => $status,
        ]);

        return response()->json(['message' => 'Check-in berhasil!', 'data' => $attendance]);
    }

    /**
     * Endpoint untuk Check-out
     */
    public function checkOut(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = $request->user();
        $today = Carbon::today();

        // Cari absensi check-in hari ini
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('check_in_time', $today)
            ->whereNull('check_out_time')
            ->first();

        if (!$attendance) {
            return response()->json(['message' => 'Anda belum melakukan check-in hari ini atau sudah check-out.'], 422);
        }

        $attendance->update([
            'check_out_time' => Carbon::now(),
            'check_out_latitude' => $request->latitude,
            'check_out_longitude' => $request->longitude,
        ]);

        return response()->json(['message' => 'Check-out berhasil!', 'data' => $attendance]);
    }

    /**
     * Endpoint untuk pengajuan Izin/Sakit
     */
    public function submitLeave(Request $request)
    {
        $request->validate([
            'status' => 'required|in:Izin,Sakit',
            'reason' => 'required|string',
        ]);

        $user = $request->user();
        $today = Carbon::today();
        
        // Cek apakah sudah ada absensi hari ini
        $existingAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->first();

        if ($existingAttendance) {
            return response()->json(['message' => 'Anda sudah memiliki catatan kehadiran hari ini.'], 422);
        }

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'status' => $request->status,
            'reason' => $request->reason,
            'check_in_time' => Carbon::now(), // Catat waktu pengajuan
        ]);

        return response()->json(['message' => 'Pengajuan ' . $request->status . ' berhasil dikirim.', 'data' => $attendance]);
    }

    /**
     * Endpoint untuk mendapatkan status absensi hari ini
     */
    public function getTodayAttendance(Request $request)
    {
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->whereDate('created_at', Carbon::today())
            ->first();

        if ($attendance) {
            return response()->json(['data' => $attendance]);
        }

        return response()->json(['data' => null]);
    }


    /**
     * Fungsi helper untuk menghitung jarak
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Radius bumi dalam meter

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
            
        return $angle * $earthRadius;
    }
}
