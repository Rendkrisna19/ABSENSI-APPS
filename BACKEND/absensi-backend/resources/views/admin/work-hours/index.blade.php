@extends('layouts.admin')

@section('title', 'Pengaturan Jam Kerja')
@section('page-title', 'Pengaturan Jam Kerja')

@section('content')
<div x-data="workHourUI(
  @js(old('check_in_time', \Carbon\Carbon::parse($workHour->check_in_time)->format('H:i'))),
  @js(old('check_out_time', \Carbon\Carbon::parse($workHour->check_out_time)->format('H:i')))
)" x-init="init()" class="w-full max-w-4xl mx-auto space-y-6">

  {{-- Notifikasi --}}
  @if (session('success'))
    <div class="px-4 py-3 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-200 shadow-soft" role="alert">
      <p class="font-semibold"><i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}</p>
    </div>
  @endif

  {{-- Header: Jam Digital & Ringkasan --}}
  <div class="bg-gradient-to-r from-brand-600 to-brand-500 rounded-2xl p-6 md:p-7 text-white shadow-soft">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
      <div class="md:col-span-2">
        <h2 class="text-2xl md:text-3xl font-bold tracking-tight">Pengaturan Jam Kerja</h2>
        <p class="text-white/90 mt-1">Atur jam masuk dan pulang standar untuk seluruh karyawan.</p>

        <div class="mt-4 grid grid-cols-2 gap-3">
          <div class="bg-white/15 border border-white/20 rounded-xl p-3">
            <div class="text-xs opacity-90">Jam Masuk</div>
            <div class="text-lg font-semibold" x-text="ci"></div>
          </div>
          <div class="bg-white/15 border border-white/20 rounded-xl p-3">
            <div class="text-xs opacity-90">Jam Pulang</div>
            <div class="text-lg font-semibold" x-text="co"></div>
          </div>
          <div class="bg-white/15 border border-white/20 rounded-xl p-3">
            <div class="text-xs opacity-90">Durasi Kerja</div>
            <div class="text-lg font-semibold" x-text="durationStr"></div>
          </div>
          <div class="bg-white/15 border border-white/20 rounded-xl p-3">
            <div class="text-xs opacity-90">Status Sekarang</div>
            <div class="text-lg font-semibold" x-text="statusNow"></div>
          </div>
        </div>
      </div>

      {{-- Jam Digital --}}
      <div class="md:justify-self-end">
        <div class="rounded-2xl bg-white/20 border border-white/25 px-4 py-3 text-center backdrop-blur">
          <div class="text-xs uppercase tracking-widest opacity-90" x-text="dateStr"></div>
          <div class="mt-1 font-mono text-4xl md:text-5xl font-bold tracking-wider drop-shadow"
               x-text="timeStr"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Form Pengaturan --}}
  <div class="bg-white p-6 md:p-8 rounded-2xl shadow-soft border border-slate-200/60">
    <h3 class="text-xl font-bold text-slate-800 mb-6">Atur Jam Kerja Standar</h3>

    <form action="{{ route('work-hours.update', $workHour->id) }}" method="POST" class="space-y-6">
      @csrf
      @method('PUT')

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        {{-- Jam Masuk --}}
        <div>
          <label for="check_in_time" class="block text-slate-700 text-sm font-semibold mb-2">
            Jam Masuk
          </label>
          <input type="time" id="check_in_time" name="check_in_time"
                 x-model="ci"
                 class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-brand-500 focus:ring-brand-500"
                 required>
          @error('check_in_time')
            <p class="text-rose-600 text-xs mt-2">{{ $message }}</p>
          @enderror
        </div>

        {{-- Jam Pulang --}}
        <div>
          <label for="check_out_time" class="block text-slate-700 text-sm font-semibold mb-2">
            Jam Pulang
          </label>
          <input type="time" id="check_out_time" name="check_out_time"
                 x-model="co"
                 class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-brand-500 focus:ring-brand-500"
                 required>
          @error('check_out_time')
            <p class="text-rose-600 text-xs mt-2">{{ $message }}</p>
          @enderror
        </div>
      </div>

      {{-- Preview Info --}}
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl bg-brand-50 border border-brand-100 p-4">
          <div class="text-xs text-brand-700/90">Durasi Kerja</div>
          <div class="text-lg font-semibold text-brand-800" x-text="durationStr"></div>
        </div>
        <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
          <div class="text-xs text-slate-600">Batas Terlambat (contoh)</div>
          <div class="text-lg font-semibold text-slate-800" x-text="graceStr"></div>
        </div>
        <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-4">
          <div class="text-xs text-emerald-700/90">Status Sekarang</div>
          <div class="text-lg font-semibold text-emerald-800" x-text="statusNow"></div>
        </div>
      </div>

      <div class="flex items-center justify-end">
        <button type="submit"
                class="bg-brand-600 hover:bg-brand-700 text-white font-semibold px-6 py-3 rounded-xl transition">
          <i class="fa-solid fa-floppy-disk mr-2"></i>Simpan Perubahan
        </button>
      </div>
    </form>
  </div>
</div>

{{-- Alpine Logic --}}
<script>
function workHourUI(initialCI, initialCO){
  return {
    // state
    ci: initialCI || '08:00',
    co: initialCO || '17:00',
    timeStr: '',
    dateStr: '',
    durationStr: '',
    statusNow: '',
    graceStr: '',

    timer: null,

    init(){
      this._tick();
      this.timer = setInterval(() => this._tick(), 1000);
      this.$watch('ci', () => this._recompute());
      this.$watch('co', () => this._recompute());
      this._recompute();
    },

    // helpers
    _tick(){
      const now = new Date();
      // Jam digital HH:mm:ss
      const hh = String(now.getHours()).padStart(2,'0');
      const mm = String(now.getMinutes()).padStart(2,'0');
      const ss = String(now.getSeconds()).padStart(2,'0');
      this.timeStr = `${hh}:${mm}:${ss}`;

      // Tanggal nice (ID)
      const hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][now.getDay()];
      const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][now.getMonth()];
      this.dateStr = `${hari}, ${now.getDate()} ${bulan} ${now.getFullYear()}`;

      // update status now
      this._recompute();
    },

    _minutes(hm){
      // "HH:MM" -> total menit dari 00:00
      if (!hm || !/^\d{2}:\d{2}$/.test(hm)) return null;
      const [h, m] = hm.split(':').map(Number);
      return h*60 + m;
    },

    _fmtDuration(mins){
      if (mins == null || isNaN(mins)) return '-';
      const h = Math.floor(mins / 60);
      const m = mins % 60;
      if (h <= 0) return `${m} m`;
      if (m === 0) return `${h} j`;
      return `${h} j ${m} m`;
    },

    _recompute(){
      const mIn = this._minutes(this.ci);
      const mOut = this._minutes(this.co);
      if (mIn == null || mOut == null) {
        this.durationStr = '-';
        this.statusNow = '-';
        this.graceStr = '-';
        return;
      }

      // durasi kerja (handle bila co < ci -> shift lintas hari)
      const dur = (mOut >= mIn) ? (mOut - mIn) : (mOut + 1440 - mIn);
      this.durationStr = this._fmtDuration(dur);

      // grace (contoh: terlambat 15 menit dari jam masuk)
      const grace = mIn + 15;
      this.graceStr = `≤ ${String(Math.floor(grace/60)).padStart(2,'0')}:${String(grace%60).padStart(2,'0')} (±15 m)`;

      // status sekarang
      const now = new Date();
      const nowMin = now.getHours()*60 + now.getMinutes();
      let within;
      if (mOut >= mIn) {
        within = nowMin >= mIn && nowMin <= mOut;
      } else {
        // shift yang melewati midnight
        within = nowMin >= mIn || nowMin <= mOut;
      }
      if (within) {
        this.statusNow = 'Sedang Jam Kerja';
      } else if ((mOut >= mIn && nowMin < mIn) || (mOut < mIn && nowMin < mIn && nowMin > mOut)) {
        this.statusNow = 'Sebelum Jam Masuk';
      } else {
        this.statusNow = 'Setelah Jam Pulang';
      }
    }
  }
}
</script>
@endsection
