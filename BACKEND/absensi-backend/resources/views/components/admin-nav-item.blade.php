{{-- resources/views/components/admin-nav-item.blade.php --}}
@props(['icon' => 'fa-circle', 'label' => '', 'href' => '#', 'active' => false])

@php
  $isActive = filter_var($active, FILTER_VALIDATE_BOOLEAN);
@endphp

<a href="{{ $href }}"
   class="group flex items-center gap-3 px-4 py-2.5 rounded-xl
          {{ $isActive ? 'bg-brand-100 text-brand-800' : 'hover:bg-brand-50 text-slate-700' }}">
  <i class="fa-solid {{ $icon }} w-6 {{ $isActive ? 'text-brand-700' : 'text-brand-600' }}"></i>
  <span class="font-medium">{{ $label }}</span>
  @if($isActive)
    <span class="ml-auto h-2 w-2 rounded-full bg-brand-600/80"></span>
  @endif
</a>
