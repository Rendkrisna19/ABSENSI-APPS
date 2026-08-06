@extends('admin.layout')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.kost.index') }}" class="text-slate-500 hover:text-cyan-600 text-sm flex items-center mb-2 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Kembali ke Daftar
        </a>
        <h1 class="text-2xl font-bold text-slate-800">Edit Data Kost</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 sm:p-8">
        <form action="{{ route('admin.kost.update', $kost->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                
                <!-- Info Utama -->
                <div class="col-span-2 border-b border-slate-100 pb-2 mb-2">
                    <h3 class="text-lg font-semibold text-slate-800">Informasi Dasar</h3>
                </div>

                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nama Kost <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $kost->name) }}" class="w-full rounded-lg border-slate-300 border px-4 py-2.5 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Harga per Bulan <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-slate-400 text-sm font-bold">Rp</span>
                        <input type="number" name="price_per_month" value="{{ old('price_per_month', $kost->price_per_month) }}" class="w-full rounded-lg border-slate-300 border pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all" required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Kota</label>
                    <input type="text" name="city" value="{{ old('city', $kost->city) }}" class="w-full rounded-lg border-slate-300 border px-4 py-2.5 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Sisa Kamar <span class="text-red-500">*</span></label>
                    <input type="number" name="available_rooms" value="{{ old('available_rooms', $kost->available_rooms) }}" class="w-full rounded-lg border-slate-300 border px-4 py-2.5 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all" required>
                </div>

                <!-- Lokasi & Maps -->
                <div class="col-span-2 border-b border-slate-100 pb-2 mb-2 mt-4">
                    <h3 class="text-lg font-semibold text-slate-800">Lokasi & Maps</h3>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Link Google Maps</label>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <div class="absolute left-3 top-2.5 text-slate-400">
                                <i data-lucide="link" class="w-4 h-4"></i>
                            </div>
                            <input type="text" id="mapInput" name="map_embed" value="{{ old('map_embed', $kost->map_embed) }}" class="w-full rounded-lg border-slate-300 border pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all" placeholder="Paste link maps atau ambil otomatis">
                        </div>
                        <button type="button" onclick="getLocation()" class="bg-slate-800 hover:bg-slate-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors flex items-center justify-center shadow-lg shadow-slate-500/20 whitespace-nowrap">
                            <i data-lucide="crosshair" class="w-4 h-4 mr-2"></i> Ambil Lokasi Saya
                        </button>
                    </div>
                    <p id="geoStatus" class="text-xs font-bold text-cyan-600 mt-1 ml-1 hidden">Sedang mencari lokasi...</p>
                </div>

                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Alamat Lengkap <span class="text-red-500">*</span></label>
                    <textarea name="address" rows="3" class="w-full rounded-lg border-slate-300 border px-4 py-2.5 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all" required>{{ old('address', $kost->address) }}</textarea>
                </div>

                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Detail Lokasi (Patokan)</label>
                    <textarea name="location_detail" rows="3" class="w-full rounded-lg border-slate-300 border px-4 py-2.5 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all">{{ old('location_detail', $kost->location_detail) }}</textarea>
                </div>

                <!-- Fasilitas & Aturan -->
                <div class="col-span-2 border-b border-slate-100 pb-2 mb-2 mt-4">
                    <h3 class="text-lg font-semibold text-slate-800">Detail Fasilitas</h3>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-3">Fasilitas Tersedia</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-100">
                        @php 
                            $facilitiesList = ['Wifi', 'AC', 'Kamar Mandi Dalam', 'Kasur', 'Lemari', 'Parkir Motor', 'Dapur Umum', 'CCTV', 'Listrik Include']; 
                            $currentFacilities = $kost->facilities ?? [];
                        @endphp
                        @foreach($facilitiesList as $fac)
                        <label class="flex items-center cursor-pointer group">
                            <div class="relative flex items-center">
                                <input type="checkbox" name="facilities[]" value="{{ $fac }}" 
                                    {{ in_array($fac, $currentFacilities) ? 'checked' : '' }}
                                    class="peer h-5 w-5 cursor-pointer appearance-none rounded border border-slate-300 shadow-sm transition-all checked:border-cyan-500 checked:bg-cyan-500 hover:shadow-md">
                                <div class="pointer-events-none absolute top-2/4 left-2/4 -translate-y-2/4 -translate-x-2/4 text-white opacity-0 transition-opacity peer-checked:opacity-100">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                </div>
                            </div>
                            <span class="ml-2 text-sm text-slate-600 group-hover:text-cyan-700 transition-colors">{{ $fac }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Kebijakan / Aturan Kost</label>
                    <textarea name="property_rules" rows="4" class="w-full rounded-lg border-slate-300 border px-4 py-2.5 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all">{{ old('property_rules', $kost->property_rules) }}</textarea>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Foto Thumbnail</label>
                    @if($kost->thumbnail)
                        <div class="mb-3 flex items-center gap-3 bg-slate-50 p-3 rounded-lg border border-slate-200 w-fit">
                            <img src="{{ asset('storage/kosts/' . $kost->thumbnail) }}" alt="Current Image" class="h-16 w-16 rounded-lg object-cover">
                            <span class="text-xs text-slate-500">Foto saat ini</span>
                        </div>
                    @endif
                    <input type="file" name="thumbnail" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-cyan-100 file:text-cyan-700 hover:file:bg-cyan-200 cursor-pointer">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100 mt-6">
                <a href="{{ route('admin.kost.index') }}" class="px-6 py-2.5 rounded-lg border border-slate-300 text-slate-600 font-medium hover:bg-slate-50 transition-all">Batal</a>
                <button type="submit" class="px-6 py-2.5 rounded-lg bg-cyan-600 text-white font-medium shadow-lg shadow-cyan-500/30 hover:bg-cyan-700 transition-all flex items-center">
                    <i data-lucide="save" class="w-4 h-4 mr-2"></i> Update Data
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reuse script geolocation -->
<script>
    function getLocation() {
        const status = document.getElementById('geoStatus');
        const input = document.getElementById('mapInput');
        if (navigator.geolocation) {
            status.classList.remove('hidden');
            status.innerText = "Sedang mencari titik lokasi...";
            navigator.geolocation.getCurrentPosition(showPosition, showError);
        } else {
            alert("Geolocation tidak didukung.");
        }
    }
    function showPosition(position) {
        const lat = position.coords.latitude;
        const long = position.coords.longitude;
        const status = document.getElementById('geoStatus');
        document.getElementById('mapInput').value = `https://www.google.com/maps?q=${lat},${long}`;
        status.innerText = "Lokasi berhasil diperbarui!";
        status.classList.add('text-green-600');
        setTimeout(() => { status.classList.add('hidden'); }, 3000);
    }
    function showError(error) {
        alert("Gagal mengambil lokasi. Pastikan GPS aktif dan izin diberikan.");
        document.getElementById('geoStatus').classList.add('hidden');
    }
</script>
@endsection