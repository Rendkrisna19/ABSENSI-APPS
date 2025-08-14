<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Dashboard Admin') • {{ config('app.name', 'Laravel') }}</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Poppins','ui-sans-serif','system-ui'] },
          colors: {
            brand:{50:'#EFF6FF',100:'#DBEAFE',200:'#BFDBFE',300:'#93C5FD',400:'#60A5FA',500:'#3B82F6',600:'#2563EB',700:'#1D4ED8',800:'#1E40AF',900:'#1E3A8A'}
          },
          boxShadow:{ soft:'0 10px 25px rgba(30,64,175,.08)' },
          keyframes:{
            fadeIn:{'0%':{opacity:0,transform:'translateY(6px)'},
                    '100%':{opacity:1,transform:'translateY(0)'}},
            pulseGlow:{'0%,100%':{boxShadow:'0 0 0 rgba(59,130,246,0)'},
                       '50%':{boxShadow:'0 0 0 8px rgba(59,130,246,.12)'}}
          },
          animation:{ fadeIn:'fadeIn .35s ease-out', pulseGlow:'pulseGlow 2s ease-in-out infinite' }
        }
      }
    }
  </script>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Alpine (untuk modal, dsb.) -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-brand-50/60 text-slate-800 font-sans">

  <div class="flex min-h-screen">
    <!-- SIDEBAR -->
    <aside id="sidebar"
      class="bg-white/90 backdrop-blur text-gray-800 w-72 space-y-4 py-6 px-3
             fixed inset-y-0 left-0 -translate-x-full md:sticky md:top-0 md:translate-x-0
             transition-transform duration-300 z-30 border-r border-slate-200 shadow-soft">

      <!-- Brand -->
      <a href="{{ route('dashboard') }}" class="px-4 flex items-center gap-3">
        <span class="p-2.5 rounded-xl bg-gradient-to-br from-brand-600 to-brand-400 text-white animate-pulseGlow">
          <i class="fa-solid fa-rocket"></i>
        </span>
        <span class="text-xl font-extrabold text-brand-700 tracking-tight">AbsensiApp</span>
      </a>

      <!-- Nav -->
      <nav class="mt-2 flex flex-col gap-2.5">
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 py-3 px-5 min-h-[44px] rounded-xl transition
                  hover:bg-brand-50 {{ request()->routeIs('dashboard') ? 'bg-brand-100 text-brand-800' : 'text-slate-700' }}">
          <i class="fa-solid fa-house w-6 {{ request()->routeIs('dashboard') ? 'text-brand-700' : 'text-brand-600' }}"></i>
          <span class="font-medium">Dashboard</span>
        </a>

        <a href="{{ route('karyawan.index') }}"
           class="flex items-center gap-3 py-3 px-5 min-h-[44px] rounded-xl transition
                  hover:bg-brand-50 {{ request()->routeIs('karyawan.*') ? 'bg-brand-100 text-brand-800' : 'text-slate-700' }}">
          <i class="fa-solid fa-users w-6 {{ request()->routeIs('karyawan.*') ? 'text-brand-700' : 'text-brand-600' }}"></i>
          <span class="font-medium">Data Karyawan</span>
        </a>

        <a href="{{ route('locations.index') }}"
           class="flex items-center gap-3 py-3 px-5 min-h-[44px] rounded-xl transition
                  hover:bg-brand-50 {{ request()->routeIs('locations.*') ? 'bg-brand-100 text-brand-800' : 'text-slate-700' }}">
          <i class="fa-solid fa-map-location-dot w-6 {{ request()->routeIs('locations.*') ? 'text-brand-700' : 'text-brand-600' }}"></i>
          <span class="font-medium">Data Lokasi</span>
        </a>

        <a href="{{ route('work-hours.index') }}"
           class="flex items-center gap-3 py-3 px-5 min-h-[44px] rounded-xl transition
                  hover:bg-brand-50 {{ request()->routeIs('work-hours.*') ? 'bg-brand-100 text-brand-800' : 'text-slate-700' }}">
          <i class="fa-solid fa-clock w-6 {{ request()->routeIs('work-hours.*') ? 'text-brand-700' : 'text-brand-600' }}"></i>
          <span class="font-medium">Pengaturan Jam</span>
        </a>

        <!-- FIX: gabungkan class (hapus duplikasi) + spacing konsisten -->
        <a href="{{ route('reports.attendance') }}"
           class="flex items-center gap-3 py-3 px-5 min-h-[44px] rounded-xl transition
                  hover:bg-brand-50 {{ request()->routeIs('reports.attendance') ? 'bg-brand-100 text-brand-800' : 'text-slate-700' }}">
          <i class="fa-solid fa-file-lines w-6 {{ request()->routeIs('reports.attendance') ? 'text-brand-700' : 'text-brand-600' }}"></i>
          <span class="font-medium">Laporan Absensi</span>
        </a>

        <div class="h-px bg-slate-200 my-1"></div>

        <a href="#"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
           class="flex items-center gap-3 py-3 px-5 min-h-[44px] rounded-xl transition hover:bg-red-50 text-red-600">
          <i class="fa-solid fa-right-from-bracket w-6"></i>
          <span class="font-semibold">Logout</span>
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
          @csrf
        </form>
      </nav>
    </aside>

    <!-- MAIN -->
    <div class="flex-1 flex flex-col min-w-0">

      <!-- Header -->
      <header class="bg-white/90 backdrop-blur shadow-sm border-b border-slate-200 p-3 md:p-4 flex justify-between items-center sticky top-0 z-20">
        <button id="menu-button" class="md:hidden text-gray-700 p-2 rounded-lg border border-slate-200 hover:bg-slate-50" aria-label="Toggle menu">
          <i class="fas fa-bars fa-lg"></i>
        </button>
        <h1 class="text-lg md:text-xl font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h1>
        <div class="text-right">
          <span class="font-semibold">{{ Auth::user()->name }}</span>
          <small class="block text-gray-500">{{ Auth::user()->role }}</small>
        </div>
      </header>

      <!-- Content -->
      <main class="flex-1 p-4 md:p-6 animate-fadeIn">
        @yield('content')
      </main>

      <!-- Footer -->
      <footer class="bg-white/90 backdrop-blur border-t border-slate-200 text-center p-4 mt-auto">
        <p class="text-gray-500 text-sm">&copy; {{ date('Y') }} <span class="font-semibold text-brand-700">AbsensiApp</span>. All Rights Reserved.</p>
      </footer>
    </div>
  </div>

  <!-- JS: hamburger -->
  <script>
    const menuButton = document.getElementById('menu-button');
    const sidebar = document.getElementById('sidebar');

    if (menuButton && sidebar) {
      menuButton.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
      });

      document.addEventListener('click', (event) => {
        const clickedInsideSidebar = sidebar.contains(event.target);
        const clickedMenuBtn = menuButton.contains(event.target);
        const isDesktop = window.matchMedia('(min-width: 768px)').matches;

        if (!isDesktop && !clickedInsideSidebar && !clickedMenuBtn) {
          if (!sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.add('-translate-x-full');
          }
        }
      });
    }
  </script>
</body>
</html>
