<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: { sans:['Poppins','ui-sans-serif','system-ui'] },
            colors: {
              brand:{50:'#EFF6FF',100:'#DBEAFE',200:'#BFDBFE',300:'#93C5FD',400:'#60A5FA',500:'#3B82F6',600:'#2563EB',700:'#1D4ED8',800:'#1E40AF',900:'#1E3A8A'}
            },
            keyframes:{
              fadeIn:{'0%':{opacity:0,transform:'translateY(6px)'},
                      '100%':{opacity:1,transform:'translateY(0)'}}
            },
            animation:{ fadeIn:'fadeIn .35s ease-out' },
            boxShadow:{ soft:'0 10px 25px rgba(30,64,175,.08)' }
          }
        }
      }
    </script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  </head>

  <body class="font-sans antialiased bg-brand-50/60 text-slate-800">
    <div class="min-h-screen flex flex-col">

      {{-- Top Navigation (tetap kompatibel dengan Jetstream/Breeze) --}}
      @include('layouts.navigation')

      {{-- Page Heading --}}
      @isset($header)
        <header class="bg-gradient-to-r from-brand-600 to-brand-500 text-white shadow-soft">
          <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            {{ $header }}
          </div>
        </header>
      @endisset

      {{-- Page Content --}}
      <main class="flex-1 animate-fadeIn">
        {{ $slot }}
      </main>

      {{-- Footer --}}
      <footer class="bg-white/90 backdrop-blur border-t border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-sm text-slate-500">
          &copy; {{ date('Y') }} <span class="font-semibold text-brand-700">{{ config('app.name', 'Laravel') }}</span>. All rights reserved.
        </div>
      </footer>
    </div>
  </body>
</html>
lo