@extends('layouts.admin')

@section('title', 'Data Karyawan')
@section('page-title', 'Data Karyawan')

@section('content')
<div x-data="karyawanCrud()" class="space-y-6">

    {{-- Header + Tombol --}}
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-soft p-5">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-slate-800">Data Karyawan</h2>
                <p class="text-slate-500 text-sm">Kelola profil, posisi, dan kontak karyawan.</p>
            </div>
            <div class="flex gap-3">
                <button @click="openCreateModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white transition">
                    <i class="fa-solid fa-plus"></i> Tambah Karyawan
                </button>
            </div>
        </div>
    </div>

    {{-- Notifikasi --}}
    @if (session('success'))
      <div class="px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700">
        <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
      </div>
    @endif

    @if ($errors->any())
      <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700">
        <p class="font-semibold mb-2"><i class="fa-solid fa-triangle-exclamation mr-2"></i>Oops! Terjadi kesalahan:</p>
        <ul class="list-disc list-inside space-y-0.5">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    {{-- Tabel --}}
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-soft">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left font-semibold px-5 py-3">No</th>
                        <th class="text-left font-semibold px-5 py-3">Profil</th>
                        <th class="text-left font-semibold px-5 py-3">Nama</th>
                        <th class="text-left font-semibold px-5 py-3">NIK</th>
                        <th class="text-left font-semibold px-5 py-3">Posisi</th>
                        <th class="text-left font-semibold px-5 py-3">No. HP</th>
                        <th class="text-left font-semibold px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($karyawan as $key => $item)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3">{{ $karyawan->firstItem() + $key }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center">
                                    <img src="{{ $item->profile_photo_path ? asset('storage/' . $item->profile_photo_path) : 'https://placehold.co/100x100/EFEFEF/AAAAAA?text=No+Image' }}"
                                         alt="Foto Profil"
                                         class="w-11 h-11 rounded-full object-cover ring-2 ring-brand-100">
                                </div>
                            </td>
                            <td class="px-5 py-3 font-medium text-slate-800">{{ $item->name }}</td>
                            <td class="px-5 py-3">{{ $item->nik }}</td>
                            <td class="px-5 py-3">{{ $item->position }}</td>
                            <td class="px-5 py-3">{{ $item->phone }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <button
                                        @click='openEditModal(@json($item))'
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 hover:bg-brand-50 text-brand-700 transition"
                                        title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <button
                                        @click="openDeleteModal({{ $item->id }})"
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 hover:bg-rose-50 text-rose-600 transition"
                                        title="Hapus">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-6 text-center text-slate-500">Tidak ada data karyawan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-5 py-4">
            {{ $karyawan->links() }}
        </div>
    </div>

    {{-- MODAL: Create --}}
    <div x-show="showCreate"
         x-transition.opacity
         class="fixed inset-0 bg-slate-900/50 flex items-center justify-center z-50"
         x-cloak>
        <div @click.away="showCreate=false"
             class="bg-white rounded-2xl shadow-soft border border-slate-200/60 p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between">
                <h3 class="text-xl font-bold text-slate-800">Tambah Karyawan Baru</h3>
                <button @click="showCreate=false" class="text-slate-500 hover:text-slate-700">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form action="{{ route('karyawan.store') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                @include('admin.karyawan.partials.form')
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showCreate=false"
                            class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50">Batal</button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: Edit --}}
    <div x-show="showEdit"
         x-transition.opacity
         class="fixed inset-0 bg-slate-900/50 flex items-center justify-center z-50"
         x-cloak>
        <div @click.away="showEdit=false"
             class="bg-white rounded-2xl shadow-soft border border-slate-200/60 p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between">
                <h3 class="text-xl font-bold text-slate-800">Edit Data Karyawan</h3>
                <button @click="showEdit=false" class="text-slate-500 hover:text-slate-700">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form :action="editUrl" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                @method('PUT')
                @include('admin.karyawan.partials.form')
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showEdit=false"
                            class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50">Batal</button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white">Update</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: Delete --}}
    <div x-show="showDelete"
         x-transition.opacity
         class="fixed inset-0 bg-slate-900/50 flex items-center justify-center z-50"
         x-cloak>
        <div @click.away="showDelete=false"
             class="bg-white rounded-2xl shadow-soft border border-slate-200/60 p-6 w-full max-w-md">
            <h3 class="text-xl font-bold text-slate-800 mb-2">Konfirmasi Hapus</h3>
            <p class="text-slate-600">Anda yakin ingin menghapus data karyawan ini? Tindakan ini tidak dapat dibatalkan.</p>
            <form :action="deleteUrl" method="POST" class="mt-6 flex justify-end gap-2">
                @csrf
                @method('DELETE')
                <button type="button" @click="showDelete=false"
                        class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50">Batal</button>
                <button type="submit"
                        class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white">Hapus</button>
            </form>
        </div>
    </div>

</div>

{{-- Alpine logic (tetap sederhana & aman) --}}
<script>
function karyawanCrud() {
  return {
    showCreate: false,
    showEdit: false,
    showDelete: false,
    editData: {},
    editUrl: '',
    deleteUrl: '',

    openCreateModal() {
      this.editData = {}; // reset
      this.showCreate = true;
    },
    openEditModal(karyawan) {
      this.editData = karyawan || {};
      this.editUrl = `{{ url('karyawan') }}/${this.editData.id}`;
      this.showEdit = true;
      // seed form inputs (untuk kasus browser tidak mem-bind otomatis ke x-model)
      queueMicrotask(() => this._syncModelToInputs());
    },
    openDeleteModal(id) {
      this.deleteUrl = `{{ url('karyawan') }}/${id}`;
      this.showDelete = true;
    },

    _syncModelToInputs(){
      // Bantu isi nilai ketika edit dibuka (inputs punya x-model)
      const root = document.currentScript.closest('[x-data]') || document.querySelector('[x-data]');
      if (!root) return;
      root.querySelectorAll('[data-model]').forEach((el)=>{
        const key = el.getAttribute('data-model');
        if (key && (key in this.editData)) {
          el.value = this.editData[key] ?? '';
          el.dispatchEvent(new Event('input', { bubbles:true })); // update x-model
        }
      });
    },
  }
}

// Auto-open modal saat ada error validasi
@isset($errors)
  @if ($errors->any())
    document.addEventListener('alpine:init', () => {
      const root = document.querySelector('[x-data]');
      if (!root) return;
      const comp = Alpine.$data(root);
      @if (old('_method') === 'PUT')
        comp.openEditModal(@json(old()));
      @else
        comp.openCreateModal();
      @endif
    });
  @endif
@endisset
</script>
@endsection
