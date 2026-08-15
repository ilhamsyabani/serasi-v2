<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal Pemohon') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.head-assets')
</head>
<body class="bg-slate-50 antialiased min-h-screen">
    @php $pemohon = Auth::guard('pemohon')->user(); @endphp

    {{--
        Header + menu mobile dalam SATU scope Alpine (x-data di <header>), agar
        tombol hamburger dan panel menu berbagi state `open`. Sebelumnya x-data
        ada di div terpisah sehingga tombol tak berfungsi.
    --}}
    <header x-data="{ open: false }" class="bg-blue-900 text-white sticky top-0 z-50 shadow-sm">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ route('pemohon.dashboard') }}" class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-xl bg-emerald-500 flex items-center justify-center shrink-0">
                    <i class="ph-fill ph-first-aid-kit text-lg" aria-hidden="true"></i>
                </div>
                <div>
                    <p class="text-sm font-bold leading-none">BBPOM</p>
                    <p class="text-[10px] text-blue-200 leading-none mt-0.5">Denah PBF</p>
                </div>
            </a>

            {{-- Toggle menu (mobile) --}}
            <button @click="open = !open" :aria-expanded="open" class="sm:hidden h-9 w-9 inline-flex items-center justify-center rounded-lg hover:bg-white/10 transition-colors" aria-label="Menu">
                <i x-show="!open" class="ph ph-list text-xl" aria-hidden="true"></i>
                <i x-show="open" x-cloak class="ph ph-x text-xl" aria-hidden="true"></i>
            </button>

            {{-- Menu user (desktop) --}}
            <div class="hidden sm:flex items-center gap-4">
                <span class="text-sm text-blue-100 truncate max-w-[180px] flex items-center gap-2">
                    <i class="ph ph-buildings" aria-hidden="true"></i>{{ $pemohon->nama_pbf }}
                </span>
                <a href="{{ route('pemohon.password.change') }}" class="text-sm text-blue-200 hover:text-white transition-colors flex items-center gap-1.5">
                    <i class="ph ph-lock" aria-hidden="true"></i>Ubah Password
                </a>
                <form method="POST" action="{{ route('pemohon.logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 text-sm text-blue-200 hover:text-white transition-colors">
                        <i class="ph ph-sign-out" aria-hidden="true"></i>Keluar
                    </button>
                </form>
            </div>
        </div>

        {{-- Panel menu (mobile) --}}
        <nav x-show="open" x-cloak x-transition class="sm:hidden border-t border-white/15 px-4 py-3 space-y-1">
            <a href="{{ route('pemohon.dashboard') }}" @class(['flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm', 'bg-white/15 font-medium' => request()->routeIs('pemohon.dashboard'), 'hover:bg-white/10' => ! request()->routeIs('pemohon.dashboard')])>
                <i class="ph ph-house" aria-hidden="true"></i> Dashboard
            </a>
            <a href="{{ route('pemohon.permohonan.index') }}" @class(['flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm', 'bg-white/15 font-medium' => request()->routeIs('pemohon.permohonan.*'), 'hover:bg-white/10' => ! request()->routeIs('pemohon.permohonan.*')])>
                <i class="ph ph-files" aria-hidden="true"></i> Permohonan
            </a>
            <a href="{{ route('pemohon.notifikasi.index') }}" @class(['flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm', 'bg-white/15 font-medium' => request()->routeIs('pemohon.notifikasi.index'), 'hover:bg-white/10' => ! request()->routeIs('pemohon.notifikasi.index')])>
                <i class="ph ph-bell" aria-hidden="true"></i> Notifikasi
            </a>
            <a href="{{ route('pemohon.password.change') }}" @class(['flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm', 'bg-white/15 font-medium' => request()->routeIs('pemohon.password.change'), 'hover:bg-white/10' => ! request()->routeIs('pemohon.password.change')])>
                <i class="ph ph-lock" aria-hidden="true"></i> Ubah Password
            </a>
            <div class="border-t border-white/15 pt-2 mt-2">
                <p class="px-3 pb-1 text-xs text-blue-200 truncate">{{ $pemohon->nama_pbf }}</p>
                <form method="POST" action="{{ route('pemohon.logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm text-blue-100 hover:bg-white/10">
                        <i class="ph ph-sign-out" aria-hidden="true"></i> Keluar
                    </button>
                </form>
            </div>
        </nav>
    </header>

    {{-- Konten utama. pb-24 menyisakan ruang untuk bottom-nav di mobile. --}}
    <main class="max-w-3xl mx-auto px-4 py-6 pb-24 sm:pb-6">
        @if(session('success'))
            <x-ui.alert type="success" class="mb-4" dismissible>{{ session('success') }}</x-ui.alert>
        @endif
        @if(session('error'))
            <x-ui.alert type="error" class="mb-4" dismissible>{{ session('error') }}</x-ui.alert>
        @endif
        @if($errors->any())
            <div x-data x-init="
                Swal.fire({
                    icon: 'error',
                    title: 'Validasi Gagal',
                    text: @js($errors->first()),
                    confirmButtonColor: '#166534'
                });
            "></div>
        @endif

        @yield('content')
    </main>

    {{-- Bottom Navigation (mobile-first) --}}
    <nav class="sm:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 px-2 py-1 z-50">
        <div class="flex items-center justify-around">
            @php $dashActive = request()->routeIs('pemohon.dashboard'); $permActive = request()->routeIs('pemohon.permohonan.*'); $notifActive = request()->routeIs('pemohon.notifikasi.index'); @endphp
            <a href="{{ route('pemohon.dashboard') }}" @class(['flex flex-col items-center py-2 px-6 text-[11px] gap-0.5', 'text-emerald-600' => $dashActive, 'text-slate-400' => ! $dashActive])>
                <i class="{{ $dashActive ? 'ph-fill' : 'ph' }} ph-house text-xl" aria-hidden="true"></i>
                <span>Beranda</span>
            </a>
            <a href="{{ route('pemohon.permohonan.index') }}" @class(['flex flex-col items-center py-2 px-6 text-[11px] gap-0.5', 'text-emerald-600' => $permActive, 'text-slate-400' => ! $permActive])>
                <i class="{{ $permActive ? 'ph-fill' : 'ph' }} ph-files text-xl" aria-hidden="true"></i>
                <span>Permohonan</span>
            </a>
            <a href="{{ route('pemohon.notifikasi.index') }}" @class(['flex flex-col items-center py-2 px-6 text-[11px] gap-0.5', 'text-emerald-600' => $notifActive, 'text-slate-400' => ! $notifActive])>
                <i class="{{ $notifActive ? 'ph-fill' : 'ph' }} ph-bell text-xl" aria-hidden="true"></i>
                <span>Notifikasi</span>
            </a>
        </div>
    </nav>

    @stack('scripts')
</body>
</html>
