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

        {{-- Notifikasi Bell + Dropdown --}}
        <div x-data="{
            open: false,
            unreadCount: 0,
            items: [],
            loading: false,
            async fetchNotif() {
                this.loading = true;
                try {
                    const res = await axios.get('/admin/notifikasi/dropdown');
                    this.items = res.data.items;
                    this.unreadCount = res.data.unread_count;
                } catch (e) { /* silent fail */ }
                this.loading = false;
            },
            async markAsRead(id) {
                try {
                    await axios.post('/admin/notifikasi/' + id + '/read');
                    this.items = this.items.map(i => i.id === id ? { ...i, is_unread: false } : i);
                    this.unreadCount = this.items.filter(i => i.is_unread).length;
                } catch (e) { /* silent fail */ }
            },
            async markAllRead() {
                try {
                    await axios.post('/admin/notifikasi/read-all');
                    this.items = this.items.map(i => ({ ...i, is_unread: false }));
                    this.unreadCount = 0;
                } catch (e) { /* silent fail */ }
            },
            async pollCount() {
                try {
                    const res = await axios.get('/admin/notifikasi/count');
                    this.unreadCount = res.data.count;
                } catch (e) { /* silent fail */ }
            }
        }" x-init="
            fetchNotif();
            setInterval(pollCount, 30000);
        " @keydown.escape.window="open = false" @click.away="open = false">
            {{-- Bell Button --}}
            <button @click="open = !open; if(open) fetchNotif();"
                    class="relative h-9 w-auto inline-flex items-center justify-center rounded-lg border transition-colors gap-1.5 px-2.5"
                    :class="unreadCount > 0
                        ? 'border-blue-300 bg-blue-50 text-blue-600 hover:bg-blue-100'
                        : 'border-slate-200 bg-white text-slate-500 hover:bg-slate-50'"
                    aria-label="Notifikasi">
                <span class="relative">
                    <i class="ph ph-bell text-lg" :class="unreadCount > 0 ? 'animate-wiggle' : ''" aria-hidden="true"></i>
                    <span x-show="unreadCount > 0"
                          x-text="unreadCount > 99 ? '99+' : unreadCount"
                          class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] inline-flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold px-1 leading-none">
                    </span>
                </span>
                <span x-show="unreadCount > 0"
                      x-transition
                      class="text-xs font-semibold text-blue-600 whitespace-nowrap">
                    Notifikasi Baru
                </span>
            </button>

            {{-- Dropdown Panel --}}
            <div x-show="open" x-cloak x-transition
                 class="absolute right-4 sm:right-6 z-50 mt-2 w-80 sm:w-96 rounded-xl border border-slate-200 bg-white shadow-xl overflow-hidden"
                 style="position: fixed; top: 70px;">
                {{-- Header --}}
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 bg-slate-50">
                    <div class="flex items-center gap-2">
                        <i class="ph ph-bell text-slate-600" aria-hidden="true"></i>
                        <h3 class="text-sm font-semibold text-slate-800">Notifikasi</h3>
                        <span x-show="unreadCount > 0"
                              x-text="unreadCount"
                              class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-red-100 text-red-600 text-xs font-bold leading-none">
                        </span>
                    </div>
                    <button x-show="unreadCount > 0"
                            @click="markAllRead()"
                            class="text-xs text-blue-600 hover:text-blue-700 font-medium transition-colors">
                        Tandai semua dibaca
                    </button>
                </div>

                {{-- Loading --}}
                <div x-show="loading" class="flex items-center justify-center py-8 text-slate-400 text-sm">
                    <i class="ph ph-circle-notch animate-spin mr-2"></i> Memuat...
                </div>

                {{-- Items --}}
                <div x-show="!loading" class="max-h-80 overflow-y-auto">
                    <template x-if="items.length === 0">
                        <div class="flex flex-col items-center justify-center py-10 text-slate-400">
                            <i class="ph ph-bell-slash text-3xl mb-2 text-slate-300"></i>
                            <p class="text-sm">Belum ada notifikasi</p>
                        </div>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <a :href="item.route"
                           @click="item.is_unread && markAsRead(item.id)"
                           :class="item.is_unread ? 'bg-blue-50/60 hover:bg-blue-100' : 'hover:bg-slate-50'"
                           class="flex items-start gap-3 px-4 py-3 border-b border-slate-100 last:border-0 transition-colors">
                            <div :class="item.is_unread ? 'bg-blue-500' : 'bg-slate-200'"
                                 class="mt-0.5 w-8 h-8 rounded-full flex items-center justify-center shrink-0">
                                <i :class="item.icon + ' text-sm'" :class="item.is_unread ? 'text-white' : 'text-slate-500'" aria-hidden="true"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 mb-0.5">
                                    <span :class="item.is_unread ? 'font-semibold text-slate-900' : 'font-medium text-slate-700'"
                                          class="text-sm leading-snug" x-text="item.label"></span>
                                    <span :class="item.channel === 'whatsapp' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'"
                                          class="inline-flex items-center justify-center px-1 py-0.5 rounded text-[10px] font-medium leading-none shrink-0"
                                          x-text="item.channel === 'whatsapp' ? 'WA' : 'Email'"></span>
                                </div>
                                <p x-show="item.no_registrasi" class="text-xs font-mono text-slate-400 leading-snug" x-text="'#' + item.no_registrasi"></p>
                                <p class="text-xs text-slate-400 mt-0.5" x-text="item.created_at"></p>
                            </div>
                            <div x-show="item.is_unread" class="shrink-0 mt-1.5">
                                <span class="inline-block w-2 h-2 rounded-full bg-blue-500"></span>
                            </div>
                        </a>
                    </template>
                </div>

                {{-- Footer --}}
                <div class="border-t border-slate-100 px-4 py-2 bg-slate-50">
                    <a href="/admin/notifikasi"
                       class="block text-center text-xs font-medium text-blue-600 hover:text-blue-700 transition-colors py-1">
                        Lihat semua notifikasi
                    </a>
                </div>
            </div>
        </div>

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
