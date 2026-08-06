@extends('admin.layout')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.user.index') }}" class="text-slate-500 hover:text-cyan-600 text-sm flex items-center mb-2 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Kembali
        </a>
        <h1 class="text-2xl font-bold text-slate-800">Tambah Pengguna Baru</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 sm:p-8">
        <form action="{{ route('admin.user.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 gap-6">
                <!-- Nama -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-lg border-slate-300 border px-4 py-2.5 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none" required>
                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="w-full rounded-lg border-slate-300 border px-4 py-2.5 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none" required>
                    @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Phone & Role -->
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">No. HP / WhatsApp</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" class="w-full rounded-lg border-slate-300 border px-4 py-2.5 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Role / Peran</label>
                        <select name="role" class="w-full rounded-lg border-slate-300 border px-4 py-2.5 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none bg-white">
                            <option value="user">User (Pencari Kost)</option>
                            <option value="admin">Admin (Pengelola)</option>
                        </select>
                    </div>
                </div>

                <!-- Password -->
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Password</label>
                        <input type="password" name="password" class="w-full rounded-lg border-slate-300 border px-4 py-2.5 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none" required>
                        @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="w-full rounded-lg border-slate-300 border px-4 py-2.5 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none" required>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-cyan-600 text-white font-medium shadow-lg shadow-cyan-500/30 hover:bg-cyan-700 transition-all flex items-center">
                        <i data-lucide="save" class="w-4 h-4 mr-2"></i> Simpan User
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection