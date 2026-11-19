{{--
    Modal dialog reusable (Alpine.js). Satu modal per halaman, dibuka lewat event:

        <x-ui.button x-on:click="$dispatch('buka-NAMA', { ... })">Buka</x-ui.button>
        <x-ui.modal name="NAMA" title="Judul"> ... </x-ui.modal>

    Isi `detail` event tersedia di slot sebagai `data` (mis. x-text="data.noReg").
    `bukaOtomatis` + `detail` dipakai untuk membuka ulang modal setelah validasi gagal.
    Tutup: tombol ×, klik latar gelap, atau tombol Escape.
--}}
@props([
    'name',
    'title' => '',
    'maxWidth' => 'md',
    'bukaOtomatis' => false,
    'detail' => [],
])
@php
    $lebar = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
    ][$maxWidth] ?? 'max-w-md';
@endphp
<div x-data="{ buka: {{ $bukaOtomatis ? 'true' : 'false' }}, data: {{ \Illuminate\Support\Js::from((object) $detail) }} }"
     x-on:buka-{{ $name }}.window="data = $event.detail; buka = true; $nextTick(() => $refs.panel.querySelector('select, textarea, input:not([type=hidden])')?.focus())"
     x-on:keydown.escape.window="buka = false"
     {{-- Tutup hanya bila latar gelap itu sendiri yang diklik. JANGAN pakai click.outside
          pada panel: dengan x-transition, panel tampil di klik yang sama sehingga klik
          tombol pembuka ikut terbaca "di luar" dan modal langsung tertutup lagi. --}}
     x-on:click.self="buka = false"
     x-show="buka" x-cloak
     x-transition.opacity.duration.150ms
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
     role="dialog" aria-modal="true" aria-labelledby="modal-{{ $name }}-judul">
    <div x-ref="panel"
         x-show="buka"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         class="w-full {{ $lebar }} rounded-xl bg-white text-left shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
            <div class="min-w-0">
                <h3 id="modal-{{ $name }}-judul" class="text-base font-semibold text-blue-900">{{ $title }}</h3>
                @isset($subtitle)
                    <p class="mt-0.5 text-xs text-slate-500">{{ $subtitle }}</p>
                @endisset
            </div>
            <button type="button" x-on:click="buka = false"
                    class="-mr-1 rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    aria-label="Tutup">
                <i class="ph ph-x text-lg" aria-hidden="true"></i>
            </button>
        </div>

        {{ $slot }}
    </div>
</div>
