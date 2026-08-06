@extends('admin.layout')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Transaksi & Sewa</h1>
        <p class="text-slate-500 text-sm">Monitoring booking, data penghuni, dan status sewa.</p>
    </div>
</div>

{{-- Notifikasi Sukses/Error --}}
@if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-6 flex items-center shadow-sm">
        <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i> {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center shadow-sm">
        <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i> {{ session('error') }}
    </div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Info Order</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Pemesan (Akun)</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Detail Penghuni</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status Sewa</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Pembayaran</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($transactions as $trx)
                <tr class="hover:bg-slate-50 transition-colors">
                    
                    {{-- 1. Info Order & Kost --}}
                    <td class="px-6 py-4 align-top">
                        <div class="font-mono text-[10px] text-slate-400 mb-1">{{ $trx->external_id }}</div>
                        <div class="font-bold text-slate-800 text-sm">{{ $trx->kost->name }}</div>
                        <div class="text-xs text-slate-500 mt-1">
                            <span class="inline-flex items-center">
                                <i data-lucide="calendar" class="w-3 h-3 mr-1"></i>
                                {{ \Carbon\Carbon::parse($trx->start_date)->format('d M Y') }}
                            </span>
                        </div>
                        <div class="text-xs font-semibold text-cyan-600 mt-1">{{ $trx->duration }} Bulan</div>
                    </td>

                    {{-- 2. Pemesan (Yang punya akun) --}}
                    <td class="px-6 py-4 align-top">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-600 mr-3">
                                {{ substr($trx->user->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="font-semibold text-slate-800 text-sm">{{ $trx->user->name }}</div>
                                <div class="text-xs text-slate-500">{{ $trx->user->email }}</div>
                            </div>
                        </div>
                    </td>

                    {{-- 3. Detail Penghuni (Fitur Baru) --}}
                    <td class="px-6 py-4 align-top">
                        @if($trx->tenant_type == 'self' || empty($trx->tenant_name))
                            <span class="inline-flex items-center px-2 py-1 rounded-md bg-slate-100 text-slate-600 text-xs font-medium">
                                <i data-lucide="user" class="w-3 h-3 mr-1"></i> Diri Sendiri
                            </span>
                        @else
                            <div class="mb-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide
                                    {{ $trx->tenant_type == 'family' ? 'bg-purple-100 text-purple-700' : '' }}
                                    {{ $trx->tenant_type == 'partner' ? 'bg-pink-100 text-pink-700' : '' }}
                                    {{ $trx->tenant_type == 'friend' ? 'bg-orange-100 text-orange-700' : '' }}
                                    bg-blue-100 text-blue-700">
                                    {{ $trx->tenant_type == 'partner' ? 'Pasangan' : ($trx->tenant_type == 'family' ? 'Keluarga' : 'Teman') }}
                                </span>
                            </div>
                            <div class="font-semibold text-slate-800 text-sm">{{ $trx->tenant_name }}</div>
                            <div class="text-xs text-slate-500 flex items-center mt-0.5">
                                <i data-lucide="phone" class="w-3 h-3 mr-1"></i> {{ $trx->tenant_phone }}
                            </div>
                        @endif
                    </td>

                    {{-- 4. Status Sewa & Lifecycle (Fitur Baru) --}}
                    <td class="px-6 py-4 align-top">
                        @if($trx->rent_status == 'ACTIVE')
                            <span class="inline-flex items-center px-2 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold border border-green-200">
                                <span class="w-2 h-2 rounded-full bg-green-500 mr-2 animate-pulse"></span> Aktif
                            </span>
                        @elseif($trx->rent_status == 'UPCOMING')
                            <span class="inline-flex items-center px-2 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                                Belum Mulai
                            </span>
                        @elseif($trx->rent_status == 'STOPPED')
                            <div class="flex flex-col gap-1">
                                <span class="inline-flex w-fit items-center px-2 py-1 rounded-full bg-red-100 text-red-700 text-xs font-bold">
                                    Berhenti (Refund)
                                </span>
                                <div class="text-[10px] text-slate-500 mt-1 bg-slate-50 p-1.5 rounded border border-slate-200">
                                    <div>Stop: {{ \Carbon\Carbon::parse($trx->stopped_at)->format('d/m/y') }}</div>
                                    <div class="text-emerald-600 font-bold">Refund: Rp {{ number_format($trx->refund_amount, 0, ',', '.') }}</div>
                                    <div class="text-red-500">Denda: Rp {{ number_format($trx->penalty_amount, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        @elseif($trx->rent_status == 'COMPLETED')
                            <span class="inline-flex items-center px-2 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-bold">
                                Selesai
                            </span>
                        @else
                             <span class="text-slate-400 text-xs">-</span>
                        @endif
                    </td>

                    {{-- 5. Total & Status Pembayaran --}}
                    <td class="px-6 py-4 align-top">
                        <div class="font-bold text-slate-700">Rp {{ number_format($trx->total_price, 0, ',', '.') }}</div>
                        <div class="mt-2">
                            @if($trx->status == 'PAID')
                                <span class="bg-emerald-100 text-emerald-700 px-2 py-1 rounded text-xs font-bold border border-emerald-200">LUNAS</span>
                            @elseif($trx->status == 'PENDING')
                                <span class="bg-amber-100 text-amber-700 px-2 py-1 rounded text-xs font-bold border border-amber-200">PENDING</span>
                            @else
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold border border-red-200">{{ $trx->status }}</span>
                            @endif
                        </div>
                    </td>

                    {{-- 6. Aksi (Update Manual) --}}
                    <td class="px-6 py-4 text-right align-top">
                        <form action="{{ route('admin.transaction.update', $trx->id) }}" method="POST">
                            @csrf @method('PUT')
                            <select name="status" onchange="if(confirm('Ubah status pembayaran? Stok kamar akan disesuaikan.')){this.form.submit()}" 
                                class="text-xs border-slate-300 rounded-lg focus:ring-cyan-500 focus:border-cyan-500 p-1.5 bg-white shadow-sm cursor-pointer hover:border-cyan-500 transition-colors">
                                <option value="PENDING" {{ $trx->status == 'PENDING' ? 'selected' : '' }}>Pending</option>
                                <option value="PAID" {{ $trx->status == 'PAID' ? 'selected' : '' }}>Set LUNAS</option>
                                <option value="FAILED" {{ $trx->status == 'FAILED' ? 'selected' : '' }}>Failed</option>
                                <option value="EXPIRED" {{ $trx->status == 'EXPIRED' ? 'selected' : '' }}>Expired</option>
                            </select>
                        </form>
                    </td>

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($transactions->isEmpty())
        <div class="p-10 text-center text-slate-500">
            <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-3 text-slate-300"></i>
            <p>Belum ada data transaksi.</p>
        </div>
    @endif
</div>
@endsection