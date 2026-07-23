<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 antialiased">
    {{-- Hero Section --}}
    <div class="min-h-screen flex flex-col">
        <div class="flex-1 flex items-center justify-center p-6">
            <div class="w-full max-w-3xl">
                {{-- Logo & Branding --}}
                <div class="text-center mb-10">
                    <div class="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-emerald-800 text-white font-bold text-2xl mb-4 shadow-lg">BP</div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Aplikasi Pengesahan Denah PBF</h1>
                    <p class="mt-2 text-sm sm:text-base text-slate-500">Balai Besar Pengawasan Obat dan Makanan</p>
                </div>

                {{-- Portal Cards --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                    <a href="{{ route('internal.login') }}"
                       class="group bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md hover:border-emerald-700 transition-all">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 rounded-xl bg-emerald-800 text-white flex items-center justify-center text-xl font-bold shrink-0 group-hover:bg-emerald-900 transition-colors">🔐</div>
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">Portal Internal BBPOM</h2>
                                <p class="text-sm text-slate-500 mt-0.5">Kepala Balai · Ketua Tim · Staff · Admin IT</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('pemohon.login') }}"
                       class="group bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md hover:border-blue-700 transition-all">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 rounded-xl bg-blue-700 text-white flex items-center justify-center text-xl font-bold shrink-0 group-hover:bg-blue-800 transition-colors">🏢</div>
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">Portal Pelaku Usaha</h2>
                                <p class="text-sm text-slate-500 mt-0.5">Pelaku Usaha (PBF)</p>
                            </div>
                        </div>
                    </a>
                </div>

                {{-- Demo Credentials --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Akun Demo Internal</h3>
                        <div class="space-y-2 text-xs font-mono">
                            <div class="flex justify-between"><span class="text-slate-500">Kepala Balai:</span><span class="text-slate-700">kepala.balai@bbpom.test</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Ketua Tim:</span><span class="text-slate-700">ketua.tim@bbpom.test</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Staff 1:</span><span class="text-slate-700">staff1@bbpom.test</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Admin IT:</span><span class="text-slate-700">admin.it@bbpom.test</span></div>
                            <div class="pt-1 border-t border-slate-100"><span class="text-slate-400">Password semua:</span> <span class="text-emerald-700 font-semibold">password</span></div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Akun Demo Pemohon</h3>
                        <div class="space-y-2 text-xs font-mono">
                            <div class="flex justify-between"><span class="text-slate-500">Email:</span><span class="text-slate-700">pemohon@contohfarma.test</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">WhatsApp:</span><span class="text-slate-700">081234567890</span></div>
                            <div class="pt-1 border-t border-slate-100"><span class="text-slate-400">Password:</span> <span class="text-emerald-700 font-semibold">password</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="py-4 text-center text-xs text-slate-400">
            © {{ date('Y') }} BBPOM — Sistem Pengesahan Denah PBF
        </footer>
    </div>
</body>
</html>
