{{-- Topbar untuk Portal Internal (design_system.md §5.5) --}}
@props(['title' => ''])
@php $user = Auth::user(); @endphp
<header class="bg-white border-b border-slate-200 px-4 sm:px-6 py-4 flex items-center justify-between gap-3 shrink-0">
    <div class="min-w-0">
        <h1 class="text-lg font-semibold text-blue-900 truncate">{{ $title }}</h1>
    </div>
    <div class="flex items-center gap-2 sm:gap-3">
        {{-- Search --}}
        <div class="relative hidden md:block">
            <input type="search" placeholder="Cari..." class="h-9 w-64 pl-9 pr-4 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500" />
            <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
        </div>
        {{-- Notifikasi --}}
        <button class="relative h-9 w-9 inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 transition-colors" aria-label="Notifikasi">
            <i class="ph ph-bell text-lg" aria-hidden="true"></i>
        </button>

        {{-- Menu user + Logout --}}
        <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
            <button @click="open = !open" :aria-expanded="open"
                    class="flex items-center gap-2 rounded-lg py-1 pl-1 pr-1.5 hover:bg-slate-50 transition-colors">
                <x-ui.avatar :name="$user->nama ?? 'U'" size="sm" />
                <span class="hidden sm:block text-sm font-medium text-slate-700 max-w-[8rem] truncate">{{ $user->nama ?? 'User' }}</span>
                <i class="ph ph-caret-down text-slate-400 text-sm" aria-hidden="true"></i>
            </button>

            <div x-show="open" x-cloak x-transition @click.outside="open = false"
                 class="absolute right-0 z-30 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
                <div class="px-3 py-2 border-b border-slate-100 mb-1">
                    <p class="text-sm font-medium text-slate-800 truncate">{{ $user->nama ?? 'User' }}</p>
                    <p class="text-xs text-slate-400 truncate">{{ $user->role?->nama ?? '' }}</p>
                </div>
                <a href="{{ route('internal.password.change') }}"
                   class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-emerald-50 hover:text-emerald-600 transition-colors">
                    <i class="ph ph-lock text-lg" aria-hidden="true"></i>
                    Ubah Password
                </a>
                <form method="POST" action="{{ route('internal.logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-red-50 hover:text-red-600 transition-colors">
                        <i class="ph ph-sign-out text-lg" aria-hidden="true"></i>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
        {{ $slot ?? '' }}
    </div>
</header>
