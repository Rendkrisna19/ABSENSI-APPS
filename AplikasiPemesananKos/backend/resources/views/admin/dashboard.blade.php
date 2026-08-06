@extends('admin.layout')

@section('content')
<div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Dashboard Overview</h1>
        <p class="text-slate-500 mt-1">Statistik ringkas dan laporan pendapatan aplikasi kost.</p>
    </div>
    <div>
        <a href="{{ route('admin.kost.create') }}" class="inline-flex items-center px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors shadow-lg shadow-cyan-500/30">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
            Tambah Kost
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-md transition-all duration-300">
        <div class="absolute right-0 top-0 h-24 w-24 bg-cyan-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
        <div class="relative z-10 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 mb-1">Total Kost</p>
                <h3 class="text-2xl font-bold text-slate-800">{{ $totalKost }}</h3>
            </div>
            <div class="w-10 h-10 bg-cyan-100 text-cyan-600 rounded-lg flex items-center justify-center">
                <i data-lucide="home" class="w-5 h-5"></i>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-md transition-all duration-300">
        <div class="absolute right-0 top-0 h-24 w-24 bg-emerald-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
        <div class="relative z-10 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 mb-1">User Terdaftar</p>
                <h3 class="text-2xl font-bold text-slate-800">{{ $totalUser }}</h3>
            </div>
            <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center">
                <i data-lucide="users" class="w-5 h-5"></i>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-md transition-all duration-300">
        <div class="absolute right-0 top-0 h-24 w-24 bg-amber-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
        <div class="relative z-10 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 mb-1">Admin</p>
                <h3 class="text-2xl font-bold text-slate-800">{{ $totalAdmin }}</h3>
            </div>
            <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-md transition-all duration-300">
        <div class="absolute right-0 top-0 h-24 w-24 bg-indigo-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
        <div class="relative z-10 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 mb-1">Total Pendapatan</p>
                <h3 class="text-2xl font-bold text-slate-800">
                    Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                </h3>
            </div>
            <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center">
                <i data-lucide="wallet" class="w-5 h-5"></i>
            </div>
        </div>
        <div class="mt-2 text-xs text-indigo-500 font-medium">
            *Semua Transaksi Sukses
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-slate-800">Grafik Pendapatan</h3>
            
            <div class="bg-slate-100 p-1 rounded-lg flex text-xs font-medium">
                <a href="{{ route('admin.dashboard', ['filter' => 'day']) }}" 
                   class="px-3 py-1.5 rounded-md transition-colors {{ $filter == 'day' ? 'bg-white text-cyan-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                   Hari
                </a>
                <a href="{{ route('admin.dashboard', ['filter' => 'month']) }}" 
                   class="px-3 py-1.5 rounded-md transition-colors {{ $filter == 'month' ? 'bg-white text-cyan-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                   Bulan
                </a>
                <a href="{{ route('admin.dashboard', ['filter' => 'year']) }}" 
                   class="px-3 py-1.5 rounded-md transition-colors {{ $filter == 'year' ? 'bg-white text-cyan-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                   Tahun
                </a>
            </div>
        </div>
        
        <div class="relative h-80 w-full">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <div class="bg-gradient-to-br from-cyan-600 to-blue-800 rounded-2xl p-8 shadow-lg text-white relative overflow-hidden flex flex-col justify-between">
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white opacity-10 rounded-full blur-2xl"></div>
        
        <div class="relative z-10">
            <h2 class="text-xl font-bold mb-3">Kelola Kost</h2>
            <p class="text-cyan-100 text-sm mb-6 leading-relaxed">
                Pantau performa bisnis kost Anda, update ketersediaan kamar, dan kelola transaksi dengan mudah.
            </p>
        </div>
        
        <div class="relative z-10">
            <a href="{{ route('admin.kost.index') }}" class="bg-white text-cyan-700 hover:bg-cyan-50 px-5 py-3 rounded-xl font-bold text-sm shadow-md transition-all flex items-center justify-center w-full">
                <i data-lucide="list" class="w-4 h-4 mr-2"></i> Kelola Data Kost
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        
        // Data dari Controller
        const labels = @json($chartLabels);
        const dataValues = @json($chartData);
        
        // Setup Gradient Warna
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(6, 182, 212, 0.5)'); // Cyan transparan
        gradient.addColorStop(1, 'rgba(6, 182, 212, 0.0)');

        new Chart(ctx, {
            type: 'line', // Bisa diganti 'bar' jika suka grafik batang
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: dataValues,
                    borderColor: '#0891b2', // Cyan-600
                    backgroundColor: gradient,
                    borderWidth: 2,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#0891b2',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.3 // Membuat garis melengkung halus
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [2, 4],
                            color: '#f1f5f9'
                        },
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + (value / 1000000).toFixed(1) + 'jt'; // Format sumbu Y (Juta)
                            },
                            font: {
                                size: 11
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endsection