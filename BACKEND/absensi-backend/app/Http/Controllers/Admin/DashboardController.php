<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use App\Models\WorkHour;
use App\Models\Location;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // Stats
        $totalEmployees = User::count();

        $presentToday = Attendance::whereDate('created_at', $today)
            ->whereIn('status', ['Hadir','Terlambat'])
            ->count();

        $lateToday = Attendance::whereDate('created_at', $today)
            ->where('status', 'Terlambat')
            ->count();

        // "Absen" = tidak ada record hadir/terlambat/izin/sakit untuk hari ini
        // Sesuaikan definisi sesuai bisnis kamu
        $izinSakitToday = Attendance::whereDate('created_at', $today)
            ->whereIn('status', ['Izin','Sakit'])
            ->count();

        $absentToday = max($totalEmployees - ($presentToday + $izinSakitToday), 0);

        $stats = compact('totalEmployees','presentToday','lateToday','absentToday');

        // WorkHour & lokasi aktif (sesuaikan logikanya)
        $workHour = WorkHour::first();
        $activeLocationName = Location::latest('id')->value('name') ?? '—';

        // Aktivitas terbaru
        $recentAttendances = Attendance::with(['user','location'])
            ->latest()
            ->limit(8)
            ->get();

        // Chart 14 hari ke belakang
        $labels = [];
        $present = [];
        $late = [];
        $absent = [];

        for ($i = 13; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $labels[] = $d->isoFormat('D MMM');

            $p = Attendance::whereDate('created_at', $d)->whereIn('status', ['Hadir','Terlambat'])->count();
            $l = Attendance::whereDate('created_at', $d)->where('status', 'Terlambat')->count();

            // Absen harian dihitung kasar: totalUsers - (hadir/terlambat + izin/sakit)
            $izinSakit = Attendance::whereDate('created_at', $d)->whereIn('status', ['Izin','Sakit'])->count();
            $a = max($totalEmployees - ($p + $izinSakit), 0);

            $present[] = $p;
            $late[] = $l;
            $absent[] = $a;
        }
        $chartDaily = ['labels'=>$labels, 'present'=>$present, 'late'=>$late, 'absent'=>$absent];

        // Distribusi status hari ini
        $statusToday = [
            'Hadir'     => Attendance::whereDate('created_at', $today)->where('status','Hadir')->count(),
            'Terlambat' => $lateToday,
            'Izin'      => Attendance::whereDate('created_at', $today)->where('status','Izin')->count(),
            'Sakit'     => Attendance::whereDate('created_at', $today)->where('status','Sakit')->count(),
            'Absen'     => $absentToday,
        ];

        return view('dashboard', compact('stats','workHour','activeLocationName','recentAttendances','chartDaily','statusToday',)); // <-- koma ekstra

    }
}
