<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Barokah Kost</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        cyan: {
                            50: '#ecfeff',
                            100: '#cffafe',
                            200: '#a5f3fc',
                            300: '#67e8f9',
                            400: '#22d3ee',
                            500: '#06b6d4',
                            600: '#0891b2', 
                            700: '#0e7490',
                            800: '#155e75',
                            900: '#164e63',
                        }
                    }
                }
            }
        }
    </script>

    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        /* Desain Kotak Tegas (Sharp) */
        * { border-radius: 0 !important; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Transition untuk Sidebar Item */
        .nav-item { transition: all 0.2s ease-in-out; }
    </style>
</head>
<body class="bg-[#F4F7FE] text-slate-800 font-sans antialiased">

    <div class="flex h-screen overflow-hidden">
        
        <aside class="w-72 bg-white flex flex-col hidden md:flex z-30 h-full border-r border-slate-200 shadow-[4px_0_24px_rgba(0,0,0,0.02)]">
            <div class="h-20 flex items-center px-8 bg-white border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-cyan-600 text-white">
                        <i data-lucide="building-2" class="w-6 h-6"></i>
                    </div>
                    <span class="text-2xl font-bold tracking-tight text-slate-800">Barokah<span class="text-cyan-600">Kost</span></span>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto py-6">
                
                <div class="mb-6">
                    <p class="px-8 text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3">Menu Utama</p>
                    
                    <a href="{{ route('admin.dashboard') }}" 
                       class="nav-item flex items-center px-8 py-3.5 border-r-4 
                       {{ request()->routeIs('admin.dashboard') 
                            ? 'border-cyan-600 bg-cyan-50 text-cyan-700 font-bold' 
                            : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-cyan-600 font-medium' }}">
                        <i data-lucide="layout-dashboard" class="w-5 h-5 mr-3 {{ request()->routeIs('admin.dashboard') ? 'text-cyan-600' : '' }}"></i>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('admin.kost.index') }}" 
                       class="nav-item flex items-center px-8 py-3.5 border-r-4 
                       {{ request()->routeIs('admin.kost.*') 
                            ? 'border-cyan-600 bg-cyan-50 text-cyan-700 font-bold' 
                            : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-cyan-600 font-medium' }}">
                        <i data-lucide="home" class="w-5 h-5 mr-3 {{ request()->routeIs('admin.kost.*') ? 'text-cyan-600' : '' }}"></i>
                        <span>Data Kost</span>
                    </a>
                </div>

                <div class="mb-6">
                    <p class="px-8 text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3">Administrasi</p>
                    
                    <a href="{{ route('admin.chat.index') }}" 
                       class="nav-item flex items-center px-8 py-3.5 border-r-4 
                       {{ request()->routeIs('admin.chat.*') 
                            ? 'border-cyan-600 bg-cyan-50 text-cyan-700 font-bold' 
                            : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-cyan-600 font-medium' }}">
                        <div class="relative mr-3">
                            <i data-lucide="message-square" class="w-5 h-5 {{ request()->routeIs('admin.chat.*') ? 'text-cyan-600' : '' }}"></i>
                            @php $unread = \App\Models\Chat::where('receiver_id', Auth::id())->where('is_read', false)->count(); @endphp
                            @if($unread > 0)
                                <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                                </span>
                            @endif
                        </div>
                        <span class="flex-1">Pesan Masuk</span>
                        @if($unread > 0)
                            <span class="text-[10px] bg-red-500 text-white px-2 py-0.5 font-bold">{{ $unread }}</span>
                        @endif
                    </a>

                    <a href="{{ route('admin.user.index') }}" 
                       class="nav-item flex items-center px-8 py-3.5 border-r-4 
                       {{ request()->routeIs('admin.user.*') 
                            ? 'border-cyan-600 bg-cyan-50 text-cyan-700 font-bold' 
                            : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-cyan-600 font-medium' }}">
                        <i data-lucide="users" class="w-5 h-5 mr-3 {{ request()->routeIs('admin.user.*') ? 'text-cyan-600' : '' }}"></i>
                        <span>Data Pengguna</span>
                    </a>

                    <a href="{{ route('admin.transaction.index') }}" 
                       class="nav-item flex items-center px-8 py-3.5 border-r-4 
                       {{ request()->routeIs('admin.transaction.*') 
                            ? 'border-cyan-600 bg-cyan-50 text-cyan-700 font-bold' 
                            : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-cyan-600 font-medium' }}">
                        <i data-lucide="shopping-cart" class="w-5 h-5 mr-3 {{ request()->routeIs('admin.transaction.*') ? 'text-cyan-600' : '' }}"></i>
                        <span>Transaksi</span>
                    </a>

                    <a href="{{ route('admin.refund.index') }}" 
                       class="nav-item flex items-center px-8 py-3.5 border-r-4 
                       {{ request()->routeIs('admin.refund.*') 
                            ? 'border-cyan-600 bg-cyan-50 text-cyan-700 font-bold' 
                            : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-cyan-600 font-medium' }}">
                        <i data-lucide="refresh-ccw" class="w-5 h-5 mr-3 {{ request()->routeIs('admin.refund.*') ? 'text-cyan-600' : '' }}"></i>
                        <span>Pengajuan Refund</span>
                    </a>
                </div>

            </nav>

            <div class="p-6 border-t border-slate-100">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex items-center w-full px-4 py-3 bg-slate-50 text-slate-600 hover:bg-red-50 hover:text-red-600 transition-all font-medium border border-slate-200 hover:border-red-200">
                        <i data-lucide="log-out" class="w-5 h-5 mr-3"></i>
                        <span>Keluar Sistem</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
            
            <header class="h-20 bg-[#F4F7FE] flex items-center justify-between px-8 z-20">
                <div class="flex items-center gap-4">
                    <button class="md:hidden text-slate-500 hover:text-cyan-600 transition">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-slate-800">Dashboard</h2>
                        <p class="text-xs text-slate-400">Selamat datang kembali, Admin!</p>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div class="hidden md:flex items-center bg-white px-4 py-2.5 w-72 shadow-sm border-b-2 border-transparent focus-within:border-cyan-500 transition-all">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 mr-2"></i>
                        <input type="text" placeholder="Cari data..." class="bg-transparent border-none outline-none text-sm w-full placeholder-slate-400 text-slate-700">
                    </div>

                    <div class="flex items-center gap-4 pl-6 border-l border-slate-200">
                        <div class="text-right hidden sm:block">
                            <div class="text-sm font-bold text-slate-800">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-cyan-600 font-medium">{{ Auth::user()->role }}</div>
                        </div>
                        <div class="w-11 h-11 bg-cyan-600 text-white flex items-center justify-center font-bold text-xl shadow-lg shadow-cyan-200">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-[#F4F7FE] px-8 pb-8">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>