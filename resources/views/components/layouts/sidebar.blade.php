{{-- Sidebar Navigation untuk Portal Internal (design_system.md §5.5) --}}
@props(['active' => ''])
@php
$user = Auth::user();
$role = $user?->role?->kode ?? '';
// `icon` memakai Phosphor Icons (design_system.md §4). Nama tanpa sufiks weight;
// weight fill dipakai otomatis saat item aktif.
$navItems = collect([
    // Kepala Balai
    (object)['route' => 'internal.kabalai.dashboard', 'label' => 'Dashboard', 'icon' => 'ph-squares-four', 'roles' => ['kepala_balai']],
    (object)['route' => 'internal.kabalai.permohonan.index', 'label' => 'Permohonan', 'icon' => 'ph-files', 'roles' => ['kepala_balai']],
    (object)['route' => 'internal.kabalai.disposisi.index', 'label' => 'Disposisi', 'icon' => 'ph-paper-plane-tilt', 'roles' => ['kepala_balai']],
    // Ketua Tim
    (object)['route' => 'internal.ketua_tim.dashboard', 'label' => 'Dashboard', 'icon' => 'ph-squares-four', 'roles' => ['ketua_tim']],
    (object)['route' => 'internal.ketua_tim.distribusi.index', 'label' => 'Distribusi', 'icon' => 'ph-share-network', 'roles' => ['ketua_tim']],
    // Staff Sertifikasi
    (object)['route' => 'internal.staff.dashboard', 'label' => 'Dashboard', 'icon' => 'ph-squares-four', 'roles' => ['staff_sertifikasi']],
    // Admin IT
    (object)['route' => 'internal.adminit.dashboard', 'label' => 'Dashboard', 'icon' => 'ph-squares-four', 'roles' => ['admin_it']],
    (object)['route' => 'internal.adminit.users.index', 'label' => 'Manajemen User', 'icon' => 'ph-users-three', 'roles' => ['admin_it']],
])->filter(fn($item) => in_array($role, $item->roles));
@endphp

<aside class="w-64 shrink-0 bg-white border-r border-slate-200 flex flex-col h-full">
    {{-- Logo --}}
    <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100">
        <div class="h-9 w-9 rounded-xl bg-emerald-500 flex items-center justify-center text-white shrink-0">
            <i class="ph-fill ph-first-aid-kit text-lg" aria-hidden="true"></i>
        </div>
        <div>
            <p class="text-sm font-bold text-blue-900 leading-none">BBPOM</p>
            <p class="text-[10px] text-slate-400 leading-none mt-0.5">Denah PBF</p>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        @foreach($navItems as $item)
            @php $isActive = request()->routeIs($item->route) || request()->routeIs($item->route.'.*'); @endphp
            <a href="{{ route($item->route) }}"
               @class([
                   'relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                   // Item aktif: latar hijau muda, teks & aksen navy, garis kiri tebal
                   'bg-emerald-50 text-blue-900' => $isActive,
                   'text-slate-500 hover:bg-slate-100 hover:text-slate-900' => ! $isActive,
               ])>
                <i class="{{ $isActive ? 'ph-fill' : 'ph' }} {{ $item->icon }} text-xl {{ $isActive ? 'text-emerald-600' : '' }}" aria-hidden="true"></i>
                {{ $item->label }}
            </a>
        @endforeach
    </nav>

    {{-- User Footer + Logout --}}
    <!-- <div class="p-3 border-t border-slate-100">
        <div class="flex items-center gap-3 px-2 py-2">
            <x-ui.avatar :name="$user->nama ?? 'U'" size="sm" />
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-800 truncate">{{ $user->nama ?? 'User' }}</p>
                <p class="text-xs text-slate-400 truncate">{{ $user->role?->nama ?? '' }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('internal.logout') }}" class="mt-1">
            @csrf
            <button type="submit"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500 hover:bg-red-50 hover:text-red-600 transition-colors">
                <i class="ph ph-sign-out text-xl" aria-hidden="true"></i>
                Keluar
            </button>
        </form>
    </div> -->
</aside>
