@extends('admin.layout')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Manajemen Pengguna</h1>
        <p class="text-slate-500 text-sm">Kelola data Admin dan User biasa.</p>
    </div>
    <a href="{{ route('admin.user.create') }}" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg shadow-md shadow-cyan-500/20 transition-all flex items-center">
        <i data-lucide="user-plus" class="w-4 h-4 mr-2"></i> Tambah User
    </a>
</div>

@if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-6 flex items-center">
        <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i> {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
        <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i> {{ session('error') }}
    </div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Nama</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Email & Kontak</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Bergabung</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($users as $user)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-cyan-100 text-cyan-600 flex items-center justify-center font-bold mr-3">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                            <span class="font-semibold text-slate-700">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($user->role == 'admin')
                            <span class="bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-xs font-bold flex w-fit items-center">
                                <i data-lucide="shield" class="w-3 h-3 mr-1"></i> Admin
                            </span>
                        @else
                            <span class="bg-slate-100 text-slate-600 px-3 py-1 rounded-full text-xs font-bold flex w-fit items-center">
                                <i data-lucide="user" class="w-3 h-3 mr-1"></i> User
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <div class="text-slate-700">{{ $user->email }}</div>
                        <div class="text-slate-400 text-xs mt-0.5">{{ $user->phone ?? '-' }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-500">
                        {{ $user->created_at->format('d M Y') }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.user.edit', $user->id) }}" class="p-2 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition-colors" title="Edit">
                                <i data-lucide="edit-2" class="w-4 h-4"></i>
                            </a>
                            
                            <!-- Tombol Hapus -->
                            @if(auth()->id() !== $user->id)
                            <form action="{{ route('admin.user.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Yakin hapus user ini?')">
                                @csrf @method('DELETE')
                                <button class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors" title="Hapus">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection