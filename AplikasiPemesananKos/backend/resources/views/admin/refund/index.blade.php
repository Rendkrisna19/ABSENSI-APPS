@extends('admin.layout')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Pengajuan Refund</h1>
    <p class="text-slate-500 text-sm">Daftar penyewa yang berhenti sewa dan menunggu pengembalian dana.</p>
</div>

@if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-6 flex items-center">
        <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i> {{ session('success') }}
    </div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal Stop</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Penyewa & Kost</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Rekening Tujuan</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Rincian Dana</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($refunds as $refund)
                <tr class="hover:bg-slate-50 transition-colors">
                    
                    {{-- 1. Tanggal --}}
                    <td class="px-6 py-4 align-top text-sm text-slate-600">
                        {{ \Carbon\Carbon::parse($refund->stopped_at)->format('d M Y') }}
                        <div class="text-[10px] text-slate-400 mt-1">{{ $refund->external_id }}</div>
                    </td>

                    {{-- 2. Info User --}}
                    <td class="px-6 py-4 align-top">
                        <div class="font-bold text-slate-800">{{ $refund->user->name }}</div>
                        <div class="text-xs text-slate-500">{{ $refund->kost->name }}</div>
                    </td>

                    {{-- 3. Rekening --}}
                    <td class="px-6 py-4 align-top">
                        <div class="bg-yellow-50 border border-yellow-200 rounded p-2 text-xs text-yellow-800 font-mono w-fit">
                            <div class="font-bold">{{ $refund->refund_bank_name }}</div>
                            <div class="text-sm">{{ $refund->refund_account_number }}</div>
                            <div class="uppercase mt-1 text-[10px] text-yellow-600">A.N {{ $refund->refund_account_name }}</div>
                        </div>
                    </td>

                    {{-- 4. Nominal --}}
                    <td class="px-6 py-4 align-top">
                        <div class="flex flex-col gap-1 text-sm">
                            <div class="flex justify-between w-40">
                                <span class="text-slate-500">Sisa Sewa:</span>
                                <span class="font-medium">Rp {{ number_format($refund->refund_amount + $refund->penalty_amount) }}</span>
                            </div>
                            <div class="flex justify-between w-40 text-red-500">
                                <span>Denda (10%):</span>
                                <span>- Rp {{ number_format($refund->penalty_amount) }}</span>
                            </div>
                            <div class="border-t border-slate-200 my-1"></div>
                            <div class="flex justify-between w-40 font-bold text-emerald-600">
                                <span>Total Refund:</span>
                                <span>Rp {{ number_format($refund->refund_amount) }}</span>
                            </div>
                        </div>
                    </td>

                    {{-- 5. Aksi --}}
                    <td class="px-6 py-4 align-top text-right">
                        <form action="{{ route('admin.refund.complete', $refund->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin sudah mentransfer dana ke user? Status akan berubah menjadi REFUNDED.')">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2 px-4 rounded-lg shadow-sm transition-all flex items-center ml-auto">
                                <i data-lucide="check-square" class="w-4 h-4 mr-2"></i>
                                Sudah Transfer
                            </button>
                        </form>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                        <div class="flex flex-col items-center justify-center">
                            <div class="bg-slate-100 p-4 rounded-full mb-3">
                                <i data-lucide="check-circle" class="w-8 h-8 text-slate-400"></i>
                            </div>
                            <p class="font-medium">Tidak ada pengajuan refund saat ini.</p>
                            <p class="text-xs mt-1">Semua aman terkendali.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection