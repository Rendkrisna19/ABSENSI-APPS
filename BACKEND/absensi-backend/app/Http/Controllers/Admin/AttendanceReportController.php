<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AttendanceReportController extends Controller
{
    /**
     * Menampilkan halaman laporan absensi dengan filter.
     */
    public function index(Request $request)
    {
        // Ambil query builder untuk model Attendance
        $query = Attendance::query()->with('user');

        // Terapkan filter jika ada input dari request
        $query->when($request->filled('start_date'), function ($q) use ($request) {
            $q->whereDate('created_at', '>=', $request->start_date);
        });

        $query->when($request->filled('end_date'), function ($q) use ($request) {
            $q->whereDate('created_at', '<=', $request->end_date);
        });

        $query->when($request->filled('user_id'), function ($q) use ($request) {
            $q->where('user_id', $request->user_id);
        });

        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('status', $request->status);
        });

        // Ambil data yang sudah difilter, urutkan dari yang terbaru, dan paginasi
        $attendances = $query->latest()->paginate(20)->withQueryString();

        // Ambil daftar karyawan untuk dropdown filter
        $employees = User::where('role', 'karyawan')->orderBy('name')->get();

        return view('admin.reports.index', compact('attendances', 'employees'));
    }
}
