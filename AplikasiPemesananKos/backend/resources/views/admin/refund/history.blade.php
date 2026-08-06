@extends('admin.layout')

@section('content')

{{-- STYLE KHUSUS PRINT --}}
<style>
    @media print {
        /* Sembunyikan elemen UI Admin (Sidebar, Header, Tombol, Footer) */
        aside, header, footer, .no-print { 
            display: none !important; 
        }

        /* Reset Layout agar full width kertas */
        body, main { 
            background: white !important; 
            margin: 0 !important; 
            padding: 0 !important; 
            width: 100% !important;
            font-family: 'Times New Roman', serif; /* Font formal untuk laporan */
        }

        /* Tampilkan Kop Laporan (Hanya muncul saat print) */
        .print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid black;
            padding-bottom: 10px;
        }

        /* Styling Tabel Print */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th, td {
            border: 1px solid black !important;
            padding: 8px;
            color: black !important;
        }
        th {
            background-color: #f0f0f0 !important;
            -webkit-print-color-adjust: exact; 
        }

        /* Area Tanda Tangan */
        .signature-area {
            display: flex !important;
            justify-content: flex-end;
            margin-top: 50px;
        }
    }
</style>

{{-- HEADER HALAMAN (TAMPILAN WEB) --}}
<div class="mb-6 flex justify-between items-center no-print">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Riwayat Pengembalian Dana</h1>
        <p class="text-slate-500 text-sm">Data refund yang telah berhasil diproses.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.refund.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg text-sm font-bold hover:bg-slate-300 transition">
            Kembali
        </a>
        <button onclick="window.print()" class="px-4 py-2 bg-cyan-600 text-white rounded-lg text-sm font-bold hover:bg-cyan-700 transition flex items-center shadow-md">
            <i data-lucide="printer" class="w-4 h-4 mr-2"></i> Cetak Laporan
        </button>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden p-6">
    
    {{-- KOP LAPORAN (HANYA MUNCUL SAAT PRINT) --}}
    <div class="print-header hidden">
        <h1 class="text-xl font-bold uppercase">KOST APP INDONESIA</h1>
        <p class="text-sm">Jl. Teknologi No. 123, Jakarta Selatan, Indonesia</p>
        <p class="text-sm">Email: admin@kostapp.id | Telp: 0812-3456-7890</p>
        <br>
        <h2 class="text-lg font-bold underline">LAPORAN RIWAYAT REFUND DANA</h2>
        <p class="text-xs text-right">Dicetak pada: {{ now()->format('d F Y H:i') }}</p>
    </div>

    {{-- TABEL --}}
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border border-slate-200">No</th>
                    <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border border-slate-200">ID Transaksi</th>
                    <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border border-slate-200">Tanggal Selesai</th>
                    <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border border-slate-200">Penyewa</th>
                    <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border border-slate-200">Kost</th>
                    <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border border-slate-200">Tujuan Transfer</th>
                    <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border border-slate-200 text-right">Nominal Refund</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($refunds as $index => $trx)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3 text-xs text-slate-600 border border-slate-100 text-center">{{ $index + 1 }}</td>
                    <td class="px-4 py-3 text-xs font-mono text-slate-500 border border-slate-100">{{ $trx->external_id }}</td>
                    <td class="px-4 py-3 text-xs text-slate-600 border border-slate-100">
                        {{ \Carbon\Carbon::parse($trx->updated_at)->format('d/m/Y') }}
                    </td>
                    <td class="px-4 py-3 text-sm font-semibold text-slate-700 border border-slate-100">{{ $trx->user->name }}</td>
                    <td class="px-4 py-3 text-sm text-slate-600 border border-slate-100">{{ $trx->kost->name }}</td>
                    <td class="px-4 py-3 text-xs text-slate-600 border border-slate-100">
                        <div class="font-bold">{{ $trx->refund_bank_name }}</div>
                        <div>{{ $trx->refund_account_number }}</div>
                        <div class="italic">a.n {{ $trx->refund_account_name }}</div>
                    </td>
                    <td class="px-4 py-3 text-sm font-bold text-emerald-700 text-right border border-slate-100">
                        Rp {{ number_format($trx->refund_amount, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-slate-500 border border-slate-100">
                        Belum ada riwayat refund yang selesai.
                    </td>
                </tr>
                @endforelse
                
                {{-- TOTAL ROW (Hanya muncul jika ada data) --}}
                @if($refunds->isNotEmpty())
                <tr class="bg-slate-50 font-bold">
                    <td colspan="6" class="px-4 py-3 text-right text-slate-700 border border-slate-200">TOTAL DANA DIKEMBALIKAN</td>
                    <td class="px-4 py-3 text-right text-emerald-700 border border-slate-200">
                        Rp {{ number_format($refunds->sum('refund_amount'), 0, ',', '.') }}
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- AREA TANDA TANGAN (HANYA PRINT) --}}
    <div class="signature-area hidden mt-10">
        <div class="text-center w-64">
            <p class="mb-20">Jakarta, {{ now()->format('d F Y') }}</p>
            <p class="font-bold underline">{{ Auth::user()->name }}</p>
            <p>Admin Pengelola</p>
        </div>
    </div>

</div>
@endsection