{{-- Sidebar Navigation untuk Portal Internal (design_system.md §5.5) --}}
@props(['active' => ''])
@php
$user = Auth::user();
$role = $user?->role?->kode ?? '';
// `icon` memakai Phosphor Icons (design_system.md §4). Nama tanpa sufiks weight;
// weight fill dipakai otomatis saat item aktif.
// Item dengan property `section` memulai grup visual.
// Item dengan property `children` akan di-expand sebagai sub-menu.
$navItems = collect([
    // ── Dashboard ──
    (object)[
        'section' => 'Dashboard',
        'items' => collect([
            (object)['route' => 'internal.kabalai.dashboard', 'label' => 'Dashboard', 'icon' => 'ph-squares-four', 'roles' => ['kepala_balai']],
            (object)['route' => 'internal.ketua_tim.dashboard', 'label' => 'Dashboard', 'icon' => 'ph-squares-four', 'roles' => ['ketua_tim']],
            (object)['route' => 'internal.staff.dashboard', 'label' => 'Dashboard', 'icon' => 'ph-squares-four', 'roles' => ['staff_sertifikasi']],
            (object)['route' => 'internal.adminit.dashboard', 'label' => 'Dashboard', 'icon' => 'ph-squares-four', 'roles' => ['admin_it']],
        ])
    ],

    // ── Permohonan ──
    (object)[
        'section' => 'Permohonan',
        'items' => collect([
            (object)['route' => 'internal.kabalai.permohonan.index', 'label' => 'Semua Permohonan', 'icon' => 'ph-files', 'roles' => ['kepala_balai']],
            (object)['route' => 'internal.kabalai.disposisi.index', 'label' => 'Disposisi', 'icon' => 'ph-paper-plane-tilt', 'roles' => ['kepala_balai']],
            (object)['route' => 'internal.ketua_tim.distribusi.index', 'label' => 'Distribusi', 'icon' => 'ph-share-network', 'roles' => ['ketua_tim']],
            // Staff: sub-menu
            (object)[
                'route' => 'internal.staff.permohonan.index',
                'label' => 'Permohonan',
                'icon' => 'ph-files',
                'iconSub' => 'ph-caret-down',
                'roles' => ['staff_sertifikasi'],
            ],
        ])
    ],

    // ── Manajemen (Admin IT only) ──
    (object)[
        'section' => 'Manajemen',
        'items' => collect([
            (object)['route' => 'internal.adminit.users.index', 'label' => 'Manajemen User', 'icon' => 'ph-users-three', 'roles' => ['admin_it']],
            (object)['route' => 'internal.adminit.sla-config.index', 'label' => 'Konfigurasi SLA', 'icon' => 'ph-clock-countdown', 'roles' => ['admin_it']],
            (object)['route' => 'internal.adminit.hari-libur.index', 'label' => 'Hari Libur & Cuti', 'icon' => 'ph-calendar-x', 'roles' => ['admin_it']],
            (object)['route' => 'internal.adminit.config-setting.index', 'label' => 'Pengaturan', 'icon' => 'ph-gear', 'roles' => ['admin_it']],
        ])
    ],

    // ── Notifikasi (semua role internal) ──
    (object)[
        'section' => 'Lainnya',
        'items' => collect([
            (object)['route' => 'internal.notifikasi.index', 'label' => 'Notifikasi Saya', 'icon' => 'ph-bell', 'roles' => ['kepala_balai', 'ketua_tim', 'staff_sertifikasi', 'admin_it']],
            (object)['route' => 'internal.kabalai.notifikasi-log.index', 'label' => 'Log Notifikasi', 'icon' => 'ph-list-checks', 'roles' => ['admin_it']],
        ])
    ],
]);

// Hilangkan section yang tidak punya item untuk role ini
$navItems = $navItems->map(fn($group) => (object)[
    'section' => $group->section,
    'items' => $group->items->filter(fn($item) => in_array($role, $item->roles))->values()
])->filter(fn($group) => $group->items->isNotEmpty());
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
    <nav class="flex-1 px-3 py-4 space-y-5 overflow-y-auto">
        @foreach($navItems as $group)
            <div>
                @if($group->section !== 'Dashboard')
                    <p class="px-3 mb-1 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">{{ $group->section }}</p>
                @endif
                @foreach($group->items as $item)
                    @php
                        $isActiveParent = request()->routeIs($item->route) || request()->routeIs($item->route.'.*');
                        $query = $item->query ?? [];
                        $hasChildren = !empty($item->children);
                        $childCount = $hasChildren ? $item->children->filter(fn($c) => in_array($role, $c->roles))->count() : 0;
                    @endphp

                    @if($hasChildren && $childCount > 0)
                        {{-- Parent item with children --}}
                        <div x-data="{ open: {{ $isActiveParent ? 'true' : 'false' }} }">
                            <button
                                @click="open = !open"
                                @class([
                                    'w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                                    'bg-emerald-50 text-blue-900' => $isActiveParent,
                                    'text-slate-500 hover:bg-slate-100 hover:text-slate-900' => ! $isActiveParent,
                                ])>
                                <span class="flex items-center gap-3">
                                    <i class="{{ $isActiveParent ? 'ph-fill' : 'ph' }} {{ $item->icon }} text-xl {{ $isActiveParent ? 'text-emerald-600' : '' }}" aria-hidden="true"></i>
                                    {{ $item->label }}
                                </span>
                                <i class="ph text-sm transition-transform" :class="open ? 'ph-caret-up' : 'ph-caret-down'" aria-hidden="true"></i>
                            </button>
                            <div x-show="open" x-collapse class="mt-1 ml-4 space-y-0.5">
                                @foreach($item->children as $child)
                                    @if(in_array($role, $child->roles))
                                        @php
                                            $childUrl = route($child->route, $query);
                                            $isActiveChild = request()->fullUrlIs($childUrl . '*') || request()->fullUrlIs($childUrl . '?*');
                                            // Check if filter matches
                                            if (isset($query['filter'])) {
                                                $isActiveChild = request()->get('filter') === $query['filter'];
                                            }
                                        @endphp
                                        <a href="{{ $childUrl }}"
                                           @class([
                                               'flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                               'bg-emerald-100 text-emerald-700' => $isActiveChild,
                                               'text-slate-500 hover:bg-slate-100 hover:text-slate-800' => ! $isActiveChild,
                                           ])>
                                            <i class="{{ $isActiveChild ? 'ph-fill' : 'ph' }} {{ $child->icon }} text-lg" aria-hidden="true"></i>
                                            {{ $child->label }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ route($item->route) }}"
                           @class([
                               'relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                               'bg-emerald-50 text-blue-900' => $isActiveParent,
                               'text-slate-500 hover:bg-slate-100 hover:text-slate-900' => ! $isActiveParent,
                           ])>
                            <i class="{{ $isActiveParent ? 'ph-fill' : 'ph' }} {{ $item->icon }} text-xl {{ $isActiveParent ? 'text-emerald-600' : '' }}" aria-hidden="true"></i>
                            {{ $item->label }}
                        </a>
                    @endif
                @endforeach
            </div>
        @endforeach
    </nav>
</aside>
