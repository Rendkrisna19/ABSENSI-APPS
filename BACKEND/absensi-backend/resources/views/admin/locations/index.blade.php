@extends('layouts.admin')

@section('title', 'Data Lokasi')
@section('page-title', 'Data Lokasi')

@section('content')
<div x-data="locationCrud()" class="space-y-6">
    <!-- Header + Tombol -->
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-soft p-5">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-slate-800">Data Lokasi</h2>
                <p class="text-slate-500 text-sm">Kelola titik geofence untuk absensi karyawan.</p>
            </div>
            <div class="flex gap-3">
                <button @click="openCreateModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white transition">
                    <i class="fa-solid fa-plus"></i> Tambah Lokasi
                </button>
            </div>
        </div>
    </div>

    {{-- Notifikasi --}}
    @include('admin.partials.notifications')

    <!-- Tabel -->
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-soft">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left font-semibold px-5 py-3">Nama Lokasi</th>
                        <th class="text-left font-semibold px-5 py-3">Alamat</th>
                        <th class="text-left font-semibold px-5 py-3">Radius (m)</th>
                        <th class="text-left font-semibold px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($locations as $item)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3 font-medium text-slate-800">{{ $item->name }}</td>
                            <td class="px-5 py-3">{{ $item->address }}</td>
                            <td class="px-5 py-3">{{ $item->radius }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <button
                                        @click='openEditModal(@json($item))'
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 hover:bg-brand-50 text-brand-700 transition"
                                        title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <form action="{{ route('locations.destroy', $item->id) }}" method="POST"
                                          class="inline-block"
                                          onsubmit="return confirm('Anda yakin ingin menghapus lokasi ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 hover:bg-rose-50 text-rose-600 transition"
                                                title="Hapus">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-6 text-center text-slate-500">Tidak ada data lokasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4">
            {{ $locations->links() }}
        </div>
    </div>

    <!-- Modal Create/Edit -->
    <div x-show="showModal" x-transition.opacity
         class="fixed inset-0 bg-slate-900/50 flex items-center justify-center z-50" x-cloak>
        <div @click.outside="showModal = false"
             class="bg-white rounded-2xl shadow-soft border border-slate-200/60 p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between">
                <h2 class="text-xl font-bold text-slate-800" x-text="isEdit ? 'Edit Data Lokasi' : 'Tambah Lokasi Baru'"></h2>
                <button @click="showModal=false" class="text-slate-500 hover:text-slate-700">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form :action="formUrl" method="POST" class="mt-5 space-y-4">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-slate-700">Nama Lokasi</label>
                        <input type="text" name="name" id="name" x-model="formData.name"
                               class="mt-1 block w-full rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-brand-500" required>
                    </div>

                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-slate-700">Alamat</label>
                        <div class="flex gap-2">
                            <textarea name="address" id="address" x-model="formData.address" rows="3"
                                      class="mt-1 block w-full rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-brand-500" required></textarea>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button type="button" @click="useCurrentLocation()"
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 hover:bg-brand-50 text-brand-700">
                                <i class="fa-solid fa-location-crosshairs"></i> Ambil Lokasi Saya
                            </button>
                            <button type="button" @click="geocodeAddress()"
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 hover:bg-brand-50 text-brand-700">
                                <i class="fa-solid fa-magnifying-glass-location"></i> Cari Koordinat dari Alamat
                            </button>
                            <span class="text-xs text-slate-500" x-text="statusText"></span>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label for="radius" class="block text-sm font-medium text-slate-700">Radius Toleransi (meter)</label>
                        <input type="number" name="radius" id="radius" x-model.number="formData.radius" min="1"
                               class="mt-1 block w-full rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-brand-500" required>
                    </div>

                    <!-- Hidden lat/lng yang diisi otomatis -->
                    <input type="hidden" name="latitude" :value="formData.latitude">
                    <input type="hidden" name="longitude" :value="formData.longitude">
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showModal=false"
                            class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50">Batal</button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white"
                            x-text="isEdit ? 'Update' : 'Simpan'"></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- AlpineJS Logic -->
<script>
function locationCrud() {
  return {
    showModal: false,
    isEdit: false,
    formUrl: '',
    statusText: '',
    formData: { id:null, name:'', address:'', latitude:null, longitude:null, radius:50 },

    openCreateModal() {
      this.isEdit = false;
      this.formUrl = @json(route('locations.store'));
      this.formData = { id:null, name:'', address:'', latitude:null, longitude:null, radius:50 };
      this.statusText = '';
      this.showModal = true;
    },
    openEditModal(location) {
      this.isEdit = true;
      this.formUrl = @json(url('locations')) + '/' + location.id;
      this.formData = {
        id: location.id,
        name: location.name || '',
        address: location.address || '',
        latitude: location.latitude || null,
        longitude: location.longitude || null,
        radius: Number(location.radius || 50),
      };
      this.statusText = '';
      this.showModal = true;
    },

    // Geolokasi browser -> koordinat -> reverse geocode adress
    async useCurrentLocation() {
      try {
        this.statusText = 'Mengambil koordinat perangkat...';
        const coords = await new Promise((resolve, reject) => {
          if (!navigator.geolocation) return reject(new Error('Geolocation tidak didukung.'));
          navigator.geolocation.getCurrentPosition(
            (pos) => resolve(pos.coords),
            (err) => reject(err),
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
          );
        });

        this.formData.latitude = coords.latitude;
        this.formData.longitude = coords.longitude;

        this.statusText = 'Mencari alamat (reverse geocoding)...';
        const addr = await this._reverseGeocode(coords.latitude, coords.longitude);
        if (addr) this.formData.address = addr;
        this.statusText = 'Lokasi terdeteksi.';
      } catch (e) {
        console.error(e);
        this.statusText = 'Gagal mendeteksi lokasi.';
        alert('Gagal mendeteksi lokasi. Pastikan izin lokasi diaktifkan.');
      }
    },

    // Geocode alamat -> koordinat
    async geocodeAddress() {
      if (!this.formData.address?.trim()) {
        alert('Isi alamat terlebih dahulu.');
        return;
      }
      try {
        this.statusText = 'Mencari koordinat dari alamat...';
        const q = encodeURIComponent(this.formData.address);
        const res = await fetch(`https://nominatim.openstreetmap.org/search?q=${q}&format=json&limit=1`);
        const data = await res.json();
        if (data?.length) {
          this.formData.latitude = parseFloat(data[0].lat);
          this.formData.longitude = parseFloat(data[0].lon);
          this.statusText = 'Koordinat ditemukan.';
        } else {
          this.statusText = 'Koordinat tak ditemukan. Coba perjelas alamat.';
          alert('Koordinat tidak ditemukan. Coba perjelas alamat.');
        }
      } catch (e) {
        console.error(e);
        this.statusText = 'Gagal geocode alamat.';
        alert('Gagal mencari koordinat dari alamat.');
      }
    },

    async _reverseGeocode(lat, lon) {
      try {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json`);
        const data = await res.json();
        return data?.display_name || '';
      } catch (e) {
        console.error(e);
        return '';
      }
    }
  }
}
</script>
@endsection
