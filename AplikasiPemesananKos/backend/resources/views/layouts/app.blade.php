<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>@yield('title','Admin')</title>

  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Optional small custom styling -->
  <style>
    body { background:#f7fafc; }
    .sidebar { width:260px; min-height:100vh; background:#fff; border-right:1px solid #e6edf3; }
    .content { padding:24px; }
  </style>

  @stack('styles')
</head>
<body>
  <div class="d-flex">
    <aside class="sidebar p-3">
      <div class="mb-4">
        <a href="{{ route('admin.dashboard') }}" class="h5 text-decoration-none">Admin Panel</a>
      </div>
      <nav class="nav flex-column">
        <a class="nav-link {{ request()->is('admin/kost') ? 'active' : '' }}" href="{{ route('admin.kost.index') }}">Daftar Kost</a>
        <a class="nav-link {{ request()->is('admin/user') ? 'active' : '' }}" href="{{ route('admin.user.index') }}">Pengguna</a>
      </nav>
      <div class="mt-4">
        <form method="POST" action="{{ route('logout') }}">@csrf
          <button class="btn btn-sm btn-outline-danger w-100">Logout</button>
        </form>
      </div>
    </aside>

    <main class="flex-fill content">
      <header class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0">@yield('page-title')</h4>
          <small class="text-muted">@yield('page-subtitle')</small>
        </div>
        <div>
          <!-- optional top-right controls -->
        </div>
      </header>

      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      @yield('content')
      <footer class="mt-5 text-muted small">© {{ date('Y') }} Barokah Zuri Kost</footer>
    </main>
  </div>

  <!-- JS libs -->
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  @stack('scripts')
</body>
</html>
