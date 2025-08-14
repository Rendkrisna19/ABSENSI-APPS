{{-- Form Fields - dipakai untuk Create & Edit --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

  {{-- Nama --}}
  <div>
    <label for="name" class="block text-sm font-medium text-slate-700">Nama Lengkap</label>
    <input
      type="text" name="name" id="name" data-model="name"
      value="{{ old('name') }}"
      x-model="editData.name"
      class="mt-1 block w-full rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-brand-500"
      required>
  </div>

  {{-- NIK --}}
  <div>
    <label for="nik" class="block text-sm font-medium text-slate-700">NIK</label>
    <input
      type="text" name="nik" id="nik" data-model="nik"
      value="{{ old('nik') }}"
      x-model="editData.nik"
      class="mt-1 block w-full rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-brand-500"
      required>
  </div>

  {{-- Email --}}
  <div>
    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
    <input
      type="email" name="email" id="email" data-model="email"
      value="{{ old('email') }}"
      x-model="editData.email"
      class="mt-1 block w-full rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-brand-500"
      required>
  </div>

  {{-- Posisi --}}
  <div>
    <label for="position" class="block text-sm font-medium text-slate-700">Posisi / Jabatan</label>
    <input
      type="text" name="position" id="position" data-model="position"
      value="{{ old('position') }}"
      x-model="editData.position"
      class="mt-1 block w-full rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-brand-500"
      required>
  </div>

  {{-- Phone --}}
  <div>
    <label for="phone" class="block text-sm font-medium text-slate-700">Nomor HP</label>
    <input
      type="text" name="phone" id="phone" data-model="phone"
      value="{{ old('phone') }}"
      x-model="editData.phone"
      class="mt-1 block w-full rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-brand-500"
      required>
  </div>

  {{-- Alamat --}}
  <div class="md:col-span-2">
    <label for="address" class="block text-sm font-medium text-slate-700">Alamat</label>
    <textarea
      name="address" id="address" rows="3" data-model="address"
      x-model="editData.address"
      class="mt-1 block w-full rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-brand-500"
      >{{ old('address') }}</textarea>
  </div>

  {{-- Password --}}
  <div>
    <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
    <input
      type="password" name="password" id="password"
      :required="!editData.id"
      class="mt-1 block w-full rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-brand-500">
    <small class="text-slate-500" x-show="editData.id">Kosongkan jika tidak ingin mengubah password.</small>
  </div>

  {{-- Konfirmasi --}}
  <div>
    <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Konfirmasi Password</label>
    <input
      type="password" name="password_confirmation" id="password_confirmation"
      class="mt-1 block w-full rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-brand-500">
  </div>

  {{-- Foto Profil --}}
  <div class="md:col-span-2">
    <label for="profile_photo" class="block text-sm font-medium text-slate-700">Foto Profil</label>
    <input
      type="file" name="profile_photo" id="profile_photo"
      class="mt-1 block w-full text-sm text-slate-600
             file:mr-4 file:py-2.5 file:px-4 file:rounded-full file:border-0
             file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">

    <template x-if="editData.profile_photo_path">
      <div class="mt-3">
        <img :src="`${@js(asset('storage'))}/${editData.profile_photo_path}`"
             alt="Current Photo" class="w-24 h-24 rounded-lg object-cover ring-1 ring-slate-200">
      </div>
    </template>
  </div>
</div>
