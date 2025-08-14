@extends('layouts.admin')

@section('title', 'Laporan Absensi')
@section('page-title', 'Laporan Absensi')

@section('content')
<div
  x-data="reportUI({
    start: @js(request('start_date')),
    end:   @js(request('end_date')),
    user:  @js(request('user_id')),
    status:@js(request('status')),
  })"
  x-init="init()"
  class="space-y-6"
>

  {{-- Header Card --}}
  <div class="bg-gradient-to-r from-brand-600 to-brand-500 rounded-2xl p-6 md:p-7 text-white shadow-soft">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h2 class="text-2xl md:text-3xl font-bold tracking-tight">Laporan Absensi</h2>
        <p class="text-white/90 mt-1">Filter, tinjau, dan tindak lanjuti data kehadiran karyawan.</p>
        <div class="mt-4 flex flex-wrap gap-2 text-xs">
          <span class="inline-flex items-center gap-1 bg-white/15 border border-white/20 px-2.5 py-1.5 rounded-lg">
            <i class="fa-solid fa-database"></i> Menampilkan {{ $attendances->count() }} data (halaman ini)
          </span>
          @if(request('start_date') || request('end_date') || request('user_id') || request('status'))
          <span class="inline-flex items-center gap-1 bg-white/15 border border-white/20 px-2.5 py-1.5 rounded-lg">
            <i class="fa-solid fa-filter"></i> Filter aktif
          </span>
          @endif
        </div>
      </div>
      {{-- Jam digital kecil --}}
      <div class="md:justify-self-end">
        <div class="rounded-2xl bg-white/20 border border-white/25 px-4 py-3 text-center backdrop-blur">
          <div class="text-xs uppercase tracking-widest opacity-90" x-text="dateStr"></div>
          <div class="mt-1 font-mono text-3xl md:text-4xl font-bold tracking-wider drop-shadow" x-text="timeStr"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Filter Section --}}
  <div class="bg-white p-5 md:p-6 rounded-2xl shadow-soft border border-slate-200/60">
    <div class="flex items-center justify-between gap-3 flex-wrap">
      <h3 class="text-lg font-semibold text-slate-800">Filter Laporan</h3>
      <div class="flex flex-wrap gap-2">
        <button type="button" @click="quickRange('today')" class="px-3 py-1.5 rounded-lg border border-white bg-brand-50 text-brand-700 hover:bg-brand-100 text-sm">
          Hari ini
        </button>
        <button type="button" @click="quickRange('week')" class="px-3 py-1.5 rounded-lg border border-white bg-slate-50 hover:bg-slate-100 text-sm">
          Minggu ini
        </button>
        <button type="button" @click="quickRange('month')" class="px-3 py-1.5 rounded-lg border border-white bg-slate-50 hover:bg-slate-100 text-sm">
          Bulan ini
        </button>
      </div>
    </div>

    <form action="{{ route('reports.attendance') }}" method="GET"
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mt-4">
      <div>
        <label for="start_date" class="block text-sm font-medium text-slate-700">Tanggal Mulai</label>
        <input x-ref="start" type="date" name="start_date" id="start_date"
               value="{{ request('start_date') }}"
               class="mt-1 block w-full rounded-xl border border-slate-200 shadow-sm focus:border-brand-500 focus:ring-brand-500">
      </div>
      <div>
        <label for="end_date" class="block text-sm font-medium text-slate-700">Tanggal Akhir</label>
        <input x-ref="end" type="date" name="end_date" id="end_date"
               value="{{ request('end_date') }}"
               class="mt-1 block w-full rounded-xl border border-slate-200 shadow-sm focus:border-brand-500 focus:ring-brand-500">
      </div>
      <div>
        <label for="user_id" class="block text-sm font-medium text-slate-700">Karyawan</label>
        <select name="user_id" id="user_id"
                class="mt-1 block w-full rounded-xl border border-slate-200 shadow-sm focus:border-brand-500 focus:ring-brand-500">
          <option value="">Semua Karyawan</option>
          @foreach ($employees as $employee)
            <option value="{{ $employee->id }}" {{ request('user_id') == $employee->id ? 'selected' : '' }}>
              {{ $employee->name }}
            </option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
        <select x-ref="status" name="status" id="status"
                class="mt-1 block w-full rounded-xl border border-slate-200 shadow-sm focus:border-brand-500 focus:ring-brand-500">
          <option value="">Semua Status</option>
          <option value="Hadir"     {{ request('status') == 'Hadir' ? 'selected' : '' }}>Hadir</option>
          <option value="Terlambat" {{ request('status') == 'Terlambat' ? 'selected' : '' }}>Terlambat</option>
          <option value="Izin"      {{ request('status') == 'Izin' ? 'selected' : '' }}>Izin</option>
          <option value="Sakit"     {{ request('status') == 'Sakit' ? 'selected' : '' }}>Sakit</option>
        </select>
      </div>
      <div class="flex items-end gap-2">
        <button type="submit" class="w-full px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white shadow">
          <i class="fa-solid fa-filter mr-2"></i> Filter
        </button>
        <a href="{{ route('reports.attendance') }}"
           class="w-full text-center px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800">
          Reset
        </a>
      </div>
    </form>

    {{-- Quick status chips --}}
    <div class="mt-3 flex flex-wrap gap-2">
      @php
        $chip = function($label,$value) {
          $active = request('status') === $value;
          $base = 'px-3 py-1.5 rounded-full text-sm border transition';
          return $active
            ? "$base bg-brand-100 text-brand-800 border-brand-200"
            : "$base bg-slate-50 text-slate-700 border-slate-200 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-200";
        };
      @endphp
      <button type="button" @click="setStatus('')"        class="{{ $chip('Semua','') }}">Semua</button>
      <button type="button" @click="setStatus('Hadir')"    class="{{ $chip('Hadir','Hadir') }}">Hadir</button>
      <button type="button" @click="setStatus('Terlambat')"class="{{ $chip('Terlambat','Terlambat') }}">Terlambat</button>
      <button type="button" @click="setStatus('Izin')"     class="{{ $chip('Izin','Izin') }}">Izin</button>
      <button type="button" @click="setStatus('Sakit')"    class="{{ $chip('Sakit','Sakit') }}">Sakit</button>
    </div>
  </div>

  {{-- Tabel Laporan --}}
  <div class="bg-white p-0 rounded-2xl shadow-soft border border-slate-200/60 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600 sticky top-0">
          <tr>
            <th class="px-5 py-3 text-left font-semibold">Tanggal</th>
            <th class="px-5 py-3 text-left font-semibold">Nama Karyawan</th>
            <th class="px-5 py-3 text-center font-semibold">Jam Masuk</th>
            <th class="px-5 py-3 text-center font-semibold">Jam Pulang</th>
            <th class="px-5 py-3 text-center font-semibold">Status</th>
            <th class="px-5 py-3 text-left font-semibold">Keterangan</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse ($attendances as $item)
            <tr class="hover:bg-slate-50/70 transition">
              <td class="px-5 py-3">
                {{ \Carbon\Carbon::parse($item->created_at)->isoFormat('dddd, D MMMM YYYY') }}
              </td>
              <td class="px-5 py-3 font-medium text-slate-800">
                {{ optional($item->user)->name ?? 'N/A' }}
              </td>
              <td class="px-5 py-3 text-center">
                {{ $item->check_in_time ? \Carbon\Carbon::parse($item->check_in_time)->format('H:i:s') : '—' }}
              </td>
              <td class="px-5 py-3 text-center">
                {{ $item->check_out_time ? \Carbon\Carbon::parse($item->check_out_time)->format('H:i:s') : '—' }}
              </td>
              <td class="px-5 py-3 text-center">
                @php
                  $statusClass = match($item->status) {
                    'Hadir'     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'Terlambat' => 'bg-amber-50 text-amber-700 border-amber-200',
                    'Izin'      => 'bg-brand-50 text-brand-700 border-brand-200',
                    'Sakit'     => 'bg-purple-50 text-purple-700 border-purple-200',
                    default     => 'bg-slate-50 text-slate-700 border-slate-200',
                  };
                @endphp
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full border text-xs font-semibold {{ $statusClass }}">
                  <i class="fa-solid fa-circle text-[6px]"></i> {{ $item->status }}
                </span>
              </td>
              <td class="px-5 py-3">{{ $item->reason ?? '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-5 py-8 text-center text-slate-500">
                <div class="inline-flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                  <i class="fa-regular fa-face-frown"></i>
                  Tidak ada data laporan yang sesuai dengan filter.
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Footer: Pagination + Aksi --}}
    <div class="p-4 md:p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3 border-t border-slate-200/60">
      <div class="text-sm text-slate-600">
        Menampilkan <b>{{ $attendances->count() }}</b> data pada halaman ini.
      </div>
      <div class="flex items-center gap-2">
        <button type="button" onclick="window.print()"
                class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-50">
          <i class="fa-solid fa-print"></i> Cetak
        </button>
        {{-- Pagination --}}
        <div>
          {{ $attendances->links() }}
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Alpine Helpers --}}
<script>
function reportUI(initial){
  return {
    timeStr:'', dateStr:'', t:null,
    init(){
      this._tick();
      this.t = setInterval(()=>this._tick(), 1000);
    },
    _tick(){
      const now = new Date();
      const hh = String(now.getHours()).padStart(2,'0');
      const mm = String(now.getMinutes()).padStart(2,'0');
      const ss = String(now.getSeconds()).padStart(2,'0');
      this.timeStr = `${hh}:${mm}:${ss}`;
      const hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][now.getDay()];
      const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][now.getMonth()];
      this.dateStr = `${hari}, ${now.getDate()} ${bulan} ${now.getFullYear()}`;
    },
    setStatus(val){
      this.$refs.status.value = val;
      this.$refs.status.form.requestSubmit();
    },
    quickRange(kind){
      const d = new Date();
      const toISO = (dt)=> dt.toISOString().slice(0,10);
      let start, end;
      if(kind==='today'){
        start = toISO(d); end = toISO(d);
      }else if(kind==='week'){
        const day = d.getDay(); // 0 Minggu
        const monday = new Date(d); monday.setDate(d.getDate() - ((day+6)%7));
        const sunday = new Date(monday); sunday.setDate(monday.getDate()+6);
        start = toISO(monday); end = toISO(sunday);
      }else{ // month
        const first = new Date(d.getFullYear(), d.getMonth(), 1);
        const last  = new Date(d.getFullYear(), d.getMonth()+1, 0);
        start = toISO(first); end = toISO(last);
      }
      this.$refs.start.value = start;
      this.$refs.end.value   = end;
      this.$refs.end.form.requestSubmit();
    },
  }
}
</script>
@endsection
