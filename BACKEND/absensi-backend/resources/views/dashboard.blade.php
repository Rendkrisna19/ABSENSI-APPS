@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php
  // Guard sederhana agar tidak error jika variabel tidak dikirim controller
  $stats = isset($stats) ? $stats : [
    'totalEmployees' => 0,
    'presentToday'   => 0,
    'lateToday'      => 0,
    'absentToday'    => 0
  ];
  $workHour = isset($workHour) ? $workHour : null;
  $activeLocationName = isset($activeLocationName) ? $activeLocationName : '—';
  $presentRate = ($stats['totalEmployees'] > 0)
      ? round(($stats['presentToday'] / $stats['totalEmployees']) * 100, 1)
      : 0;

  $chartDaily = isset($chartDaily) ? $chartDaily : ['labels'=>[], 'present'=>[], 'late'=>[], 'absent'=>[]];
  $statusToday = isset($statusToday) ? $statusToday : ['Hadir'=>0,'Terlambat'=>0,'Izin'=>0,'Sakit'=>0,'Absen'=>0];
@endphp

<div class="space-y-6">

  {{-- Greeting + CTA --}}
  <div class="bg-gradient-to-r from-brand-600 to-brand-500 rounded-2xl p-6 md:p-7 text-white shadow-soft">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h2 class="text-2xl md:text-3xl font-bold tracking-tight">
          Selamat Datang, {{ Auth::user()->name }} 👋
        </h2>
        <p class="text-white/90 mt-1">Pantau absensi karyawan dan aktivitas terbaru di sini.</p>
      </div>
      <div class="flex gap-3">
        <a href="{{ route('karyawan.index') }}"
           class="inline-flex items-center gap-2 bg-white/15 hover:bg-white/25 border border-white/20 text-white px-4 py-2 rounded-xl transition">
          <i class="fa-solid fa-users"></i> Kelola Karyawan
        </a>
        <a href="{{ route('locations.index') }}"
           class="inline-flex items-center gap-2 bg-white text-brand-700 hover:text-brand-800 px-4 py-2 rounded-xl transition shadow">
          <i class="fa-solid fa-map-location-dot"></i> Atur Lokasi
        </a>
      </div>
    </div>
  </div>

  {{-- Stats --}}
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="group bg-white rounded-2xl p-5 shadow-soft border border-slate-200/60 hover:shadow-lg hover:-translate-y-0.5 transition">
      <div class="flex items-center gap-4">
        <div class="p-3 rounded-2xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
          <i class="fa-solid fa-users text-xl"></i>
        </div>
        <div class="min-w-0">
          <p class="text-slate-500 text-sm">Total Karyawan</p>
          <p class="text-2xl font-bold text-slate-800">{{ number_format($stats['totalEmployees']) }}</p>
        </div>
      </div>
      <div class="mt-3 text-xs text-slate-500"><i class="fa-solid fa-circle-info"></i> total aktif</div>
    </div>

    <div class="group bg-white rounded-2xl p-5 shadow-soft border border-slate-200/60 hover:shadow-lg hover:-translate-y-0.5 transition">
      <div class="flex items-center gap-4">
        <div class="p-3 rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
          <i class="fa-solid fa-user-check text-xl"></i>
        </div>
        <div class="min-w-0">
          <p class="text-slate-500 text-sm">Hadir Hari Ini</p>
          <p class="text-2xl font-bold text-slate-800">{{ number_format($stats['presentToday']) }}</p>
        </div>
      </div>
      <div class="mt-3 text-xs text-slate-500">
        <span class="inline-flex items-center gap-1 text-emerald-600">
          <i class="fa-solid fa-check-circle"></i> {{ $presentRate }}% kehadiran
        </span>
      </div>
    </div>

    <div class="group bg-white rounded-2xl p-5 shadow-soft border border-slate-200/60 hover:shadow-lg hover:-translate-y-0.5 transition">
      <div class="flex items-center gap-4">
        <div class="p-3 rounded-2xl bg-amber-50 text-amber-700 ring-1 ring-amber-100">
          <i class="fa-solid fa-clock text-xl"></i>
        </div>
        <div class="min-w-0">
          <p class="text-slate-500 text-sm">Terlambat</p>
          <p class="text-2xl font-bold text-slate-800">{{ number_format($stats['lateToday']) }}</p>
        </div>
      </div>
      <div class="mt-3 text-xs text-slate-500">
        <span class="inline-flex items-center gap-1 text-amber-600">
          <i class="fa-solid fa-triangle-exclamation"></i> perlu pengingat
        </span>
      </div>
    </div>

    <div class="group bg-white rounded-2xl p-5 shadow-soft border border-slate-200/60 hover:shadow-lg hover:-translate-y-0.5 transition">
      <div class="flex items-center gap-4">
        <div class="p-3 rounded-2xl bg-rose-50 text-rose-700 ring-1 ring-rose-100">
          <i class="fa-solid fa-user-times text-xl"></i>
        </div>
        <div class="min-w-0">
          <p class="text-slate-500 text-sm">Absen</p>
          <p class="text-2xl font-bold text-slate-800">{{ number_format($stats['absentToday']) }}</p>
        </div>
      </div>
      <div class="mt-3 text-xs text-slate-500">
        <span class="inline-flex items-center gap-1 text-rose-600">
          <i class="fa-solid fa-ban"></i> tindak lanjuti HR
        </span>
      </div>
    </div>
  </div>

  {{-- Charts --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-soft border border-slate-200/60">
      <div class="p-5 border-b border-slate-200/60 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-slate-800">Tren Kehadiran 14 Hari</h3>
        <a href="{{ route('reports.attendance') }}" class="text-sm text-brand-700 hover:text-brand-800">Laporan lengkap</a>
      </div>
      <div class="p-5">
        <canvas id="chartDailyTrends" height="120"></canvas>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-soft border border-slate-200/60">
      <div class="p-5 border-b border-slate-200/60 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-slate-800">Distribusi Status Hari Ini</h3>
        <span class="text-xs text-slate-500">{{ \Carbon\Carbon::now()->format('d M Y') }}</span>
      </div>
      <div class="p-5">
        <canvas id="chartStatusToday" height="220"></canvas>
        <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
          @php
            $legend = [
              ['Hadir','text-emerald-700','bg-emerald-100'],
              ['Terlambat','text-amber-700','bg-amber-100'],
              ['Izin','text-brand-700','bg-brand-100'],
              ['Sakit','text-purple-700','bg-purple-100'],
              ['Absen','text-rose-700','bg-rose-100']
            ];
          @endphp
          @foreach ($legend as $row)
            @php
              list($label, $txt, $bg) = $row;
              $val = isset($statusToday[$label]) ? $statusToday[$label] : 0;
            @endphp
            <div class="flex items-center justify-between rounded-xl {{ $bg }} px-3 py-2">
              <span class="font-medium {{ $txt }}">{{ $label }}</span>
              <span class="font-semibold text-slate-800">{{ $val }}</span>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  {{-- Activity + widgets --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-soft border border-slate-200/60">
      <div class="p-5 border-b border-slate-200/60 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-slate-800">Aktivitas Absensi Terbaru</h3>
        <a href="{{ route('reports.attendance') }}" class="text-sm text-brand-700 hover:text-brand-800">Lihat semua</a>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-slate-600">
            <tr>
              <th class="text-left font-semibold px-5 py-3">Karyawan</th>
              <th class="text-left font-semibold px-5 py-3">Status</th>
              <th class="text-left font-semibold px-5 py-3">Check-In</th>
              <th class="text-left font-semibold px-5 py-3">Check-Out</th>
              <th class="text-left font-semibold px-5 py-3">Lokasi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse (isset($recentAttendances) ? $recentAttendances : [] as $a)
              @php
                $statusClass = 'bg-slate-50 text-slate-700 border-slate-200';
                if ($a->status === 'Hadir')      $statusClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                elseif ($a->status === 'Terlambat') $statusClass = 'bg-amber-50 text-amber-700 border-amber-200';
                elseif ($a->status === 'Izin')      $statusClass = 'bg-brand-50 text-brand-700 border-brand-200';
                elseif ($a->status === 'Sakit')     $statusClass = 'bg-purple-50 text-purple-700 border-purple-200';
              @endphp
              <tr class="hover:bg-slate-50/70 transition">
                <td class="px-5 py-3">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-brand-100 text-brand-700 grid place-items-center">
                      <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                      <div class="font-medium">{{ optional($a->user)->name ?: 'N/A' }}</div>
                      <div class="text-xs text-slate-500">{{ optional($a->user)->position ?: '—' }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-3">
                  <span class="inline-flex items-center gap-1 rounded-lg {{ $statusClass }} px-2.5 py-1">
                    <i class="fa-solid fa-circle text-[6px]"></i> {{ $a->status }}
                  </span>
                </td>
                <td class="px-5 py-3">
                  {{ $a->check_in_time ? \Carbon\Carbon::parse($a->check_in_time)->format('H:i:s') : '—' }}
                </td>
                <td class="px-5 py-3">
                  {{ $a->check_out_time ? \Carbon\Carbon::parse($a->check_out_time)->format('H:i:s') : '—' }}
                </td>
                <td class="px-5 py-3">
                  {{ optional($a->location)->name ?: (isset($a->location_name) ? $a->location_name : '—') }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="px-5 py-6 text-center text-slate-500">Belum ada aktivitas terbaru.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="p-5 border-t border-slate-200/60 text-sm text-slate-500">
        *Menampilkan {{ isset($recentAttendances) ? $recentAttendances->count() : 0 }} data terbaru.
      </div>
    </div>

    <div class="space-y-6">
      <div class="bg-white rounded-2xl shadow-soft border border-slate-200/60 p-5">
        <div class="flex items-center justify-between">
          <h4 class="font-semibold text-slate-800">Ringkasan Jam Kerja</h4>
          <span class="text-xs text-slate-500">Hari ini</span>
        </div>
        <div class="mt-4 space-y-3 text-sm">
          <div class="flex items-center justify-between">
            <span class="text-slate-600">Jam Kerja</span>
            <span class="font-medium text-slate-800">
              @if($workHour)
                {{ \Carbon\Carbon::parse($workHour->check_in_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($workHour->check_out_time)->format('H:i') }}
              @else
                —
              @endif
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-slate-600">Lokasi Aktif</span>
            <span class="font-medium text-brand-700">{{ $activeLocationName }}</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-soft border border-slate-200/60 p-5">
        <h4 class="font-semibold text-slate-800">Aksi Cepat</h4>
        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
          <a href="{{ route('karyawan.index') }}" class="group flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 hover:bg-brand-50 transition">
            <i class="fa-solid fa-user-plus text-brand-600"></i> Tambah Karyawan
          </a>
          <a href="{{ route('work-hours.index') }}" class="group flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 hover:bg-brand-50 transition">
            <i class="fa-solid fa-gear text-brand-600"></i> Atur Jam Kerja
          </a>
          <a href="{{ route('locations.index') }}" class="group flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 hover:bg-brand-50 transition">
            <i class="fa-solid fa-location-dot text-brand-600"></i> Kelola Lokasi
          </a>
          <a href="{{ route('reports.attendance') }}" class="group flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 hover:bg-brand-50 transition">
            <i class="fa-solid fa-file-export text-brand-600"></i> Ekspor Laporan
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Palet warna (JS saja; tidak terkait parser PHP)
  const brand = {
    blue:   '#3B82F6',
    blue600:'#2563EB',
    emerald:'#10B981',
    amber:  '#F59E0B',
    rose:   '#EF4444',
    purple: '#8B5CF6',
    slate:  '#64748B'
  };

  // Data dari server (pakai json_encode agar aman di semua versi Laravel/PHP)
  const dailyLabels  = {!! json_encode(isset($chartDaily['labels'])  ? $chartDaily['labels']  : []) !!};
  const dailyPresent = {!! json_encode(isset($chartDaily['present']) ? $chartDaily['present'] : []) !!};
  const dailyLate    = {!! json_encode(isset($chartDaily['late'])    ? $chartDaily['late']    : []) !!};
  const dailyAbsent  = {!! json_encode(isset($chartDaily['absent'])  ? $chartDaily['absent']  : []) !!};

  const statusToday  = {!! json_encode(array_values(isset($statusToday) ? $statusToday : ['Hadir'=>0,'Terlambat'=>0,'Izin'=>0,'Sakit'=>0,'Absen'=>0])) !!};
  const statusLabels = {!! json_encode(['Hadir','Terlambat','Izin','Sakit','Absen']) !!};

  // Line chart
  (function(){
    const el = document.getElementById('chartDailyTrends');
    if(!el) return;
    new Chart(el, {
      type: 'line',
      data: {
        labels: dailyLabels,
        datasets: [
          {
            label: 'Hadir',
            data: dailyPresent,
            borderColor: brand.emerald,
            backgroundColor: 'rgba(16,185,129,0.12)',
            fill: true,
            tension: .35,
            pointRadius: 3
          },
          {
            label: 'Terlambat',
            data: dailyLate,
            borderColor: brand.amber,
            backgroundColor: 'rgba(245,158,11,0.12)',
            fill: true,
            tension: .35,
            pointRadius: 3
          },
          {
            label: 'Absen',
            data: dailyAbsent,
            borderColor: brand.rose,
            backgroundColor: 'rgba(239,68,68,0.12)',
            fill: true,
            tension: .35,
            pointRadius: 3
          }
        ]
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        plugins: { legend: { display: true }, tooltip: { mode: 'index', intersect: false } }
      }
    });
  })();

  // Doughnut chart
  (function(){
    const el = document.getElementById('chartStatusToday');
    if(!el) return;
    new Chart(el, {
      type: 'doughnut',
      data: {
        labels: statusLabels,
        datasets: [{
          data: statusToday,
          backgroundColor: [
            'rgba(16,185,129,0.9)',
            'rgba(245,158,11,0.9)',
            'rgba(59,130,246,0.9)',
            'rgba(139,92,246,0.9)',
            'rgba(239,68,68,0.9)'
          ],
          borderWidth: 0
        }]
      },
      options: { plugins: { legend: { display: false } }, cutout: '65%' }
    });
  })();
</script>
@endsection
