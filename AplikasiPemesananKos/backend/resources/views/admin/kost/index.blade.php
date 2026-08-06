@extends('admin.layout')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Data Kost</h1>
        <p class="text-slate-500 text-sm">Kelola ketersediaan dan informasi kost Anda.</p>
    </div>
    <a href="{{ route('admin.kost.create') }}" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg shadow-md shadow-cyan-500/20 transition-all flex items-center">
        <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Tambah Kost
    </a>
</div>

@if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-6 flex items-center">
        <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
        {{ session('success') }}
    </div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Properti</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status Kamar</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Harga</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Lokasi</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($kosts as $kost)
                <tr class="hover:bg-slate-50 transition-colors group">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            @if($kost->thumbnail)
                                <img src="{{ asset('storage/kosts/' . $kost->thumbnail) }}" class="w-12 h-12 rounded-lg object-cover mr-4 shadow-sm border border-slate-100" alt="Thumb">
                            @else
                                <div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center mr-4 text-slate-400">
                                    <i data-lucide="image" class="w-6 h-6"></i>
                                </div>
                            @endif
                            <div>
                                <div class="font-semibold text-slate-800">{{ $kost->name }}</div>
                                <div class="text-xs text-slate-500 flex items-center mt-0.5">
                                    <i data-lucide="map-pin" class="w-3 h-3 mr-1"></i> {{ $kost->city ?? 'Kota belum set' }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($kost->available_rooms > 0)
                            <span class="bg-emerald-50 text-emerald-700 border border-emerald-100 px-2.5 py-1 rounded-md text-xs font-bold flex w-fit items-center">
                                <i data-lucide="door-open" class="w-3 h-3 mr-1.5"></i>
                                {{ $kost->available_rooms }} Tersedia
                            </span>
                        @else
                            <span class="bg-red-50 text-red-700 border border-red-100 px-2.5 py-1 rounded-md text-xs font-bold flex w-fit items-center">
                                <i data-lucide="door-closed" class="w-3 h-3 mr-1.5"></i>
                                Penuh
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-slate-700 font-bold text-sm">
                            Rp {{ number_format($kost->price_per_month, 0, ',', '.') }}
                        </span>
                        <div class="text-xs text-slate-400">/ bulan</div>
                    </td>
                    <td class="px-6 py-4">
                        @if($kost->map_embed)
                            <a href="{{ $kost->map_embed }}" target="_blank" class="text-cyan-600 hover:text-cyan-700 hover:underline text-xs font-medium flex items-center">
                                <i data-lucide="external-link" class="w-3 h-3 mr-1"></i> Lihat Peta
                            </a>
                        @else
                            <span class="text-slate-400 text-xs italic">- Tidak ada link -</span>
                        @endif
                        <div class="text-xs text-slate-500 mt-1 truncate max-w-[150px]" title="{{ $kost->address }}">
                            {{ Str::limit($kost->address, 25) }}
                        </div>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2 opacity-80 group-hover:opacity-100 transition-opacity">
                            <a href="{{ route('admin.kost.edit', $kost->id) }}" class="p-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-cyan-50 hover:text-cyan-600 hover:border-cyan-200 transition-all shadow-sm" title="Edit">
                                <i data-lucide="edit-2" class="w-4 h-4"></i>
                            </a>
                            <form action="{{ route('admin.kost.destroy', $kost->id) }}" method="POST" onsubmit="return confirm('Yakin hapus data ini?')">
                                @csrf @method('DELETE')
                                <button class="p-2 bg-white border border-slate-200 text-red-500 rounded-lg hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-all shadow-sm" title="Hapus">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-16 text-center text-slate-400">
                        <div class="flex flex-col items-center">
                            <div class="bg-slate-50 p-4 rounded-full mb-3">
                                <i data-lucide="inbox" class="w-8 h-8 opacity-50"></i>
                            </div>
                            <p class="font-medium text-slate-500">Belum ada data kost.</p>
                            <p class="text-sm mt-1">Silakan tambah data kost baru.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection