<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    {{-- Tambahkan baris ini HANYA JIKA Anda belum menginstal Phosphor Icons via NPM --}}
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased selection:bg-emerald-200 selection:text-emerald-900">
    {{-- Background Pattern & Gradient (Opsional, memberi kesan premium) --}}
    <div class="fixed inset-0 z-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-100 via-slate-50 to-slate-100 pointer-events-none"></div>
    
    <div class="relative z-10 min-h-screen flex flex-col">
        <div class="flex-1 flex items-center justify-center p-6">
            <div class="w-full max-w-4xl">
                
                {{-- Logo & Branding --}}
                <div class="text-center mb-12">
                    <div class="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-gradient-to-br from-emerald-700 to-emerald-900 text-white shadow-xl shadow-emerald-900/20 mb-5 ring-4 ring-emerald-50">
                        <i class="ph-fill ph-shield-plus text-3xl"></i>
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-2">
                        Aplikasi Pengesahan Denah PBF
                    </h1>
                    <p class="text-sm sm:text-base text-slate-500 font-medium">
                        Balai Besar Pengawasan Obat dan Makanan
                    </p>
                </div>

                {{-- Portal Cards --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-10">
                    {{-- Portal Internal --}}
                    <a href="{{ route('internal.login') }}"
                       class="group relative bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-xl hover:shadow-emerald-900/5 hover:-translate-y-1 hover:border-emerald-300 transition-all duration-300 ease-out overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="relative flex items-start gap-5">
                            <div class="h-14 w-14 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-300">
                                <i class="ph-duotone ph-shield-check text-3xl"></i>
                            </div>
                            <div class="pt-1">
                                <h2 class="text-lg font-bold text-slate-900 group-hover:text-emerald-800 transition-colors">Portal Internal</h2>
                                <p class="text-sm text-slate-500 mt-1 leading-relaxed">Akses khusus untuk Kepala Balai, Ketua Tim, Staff, dan Admin IT BBPOM.</p>
                            </div>
                            <div class="absolute right-6 top-1/2 -translate-y-1/2 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300 text-emerald-600">
                                <i class="ph-bold ph-arrow-right text-xl"></i>
                            </div>
                        </div>
                    </a>

                    {{-- Portal Pemohon --}}
                    <a href="{{ route('pemohon.login') }}"
                       class="group relative bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-xl hover:shadow-blue-900/5 hover:-translate-y-1 hover:border-blue-300 transition-all duration-300 ease-out overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-50/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="relative flex items-start gap-5">
                            <div class="h-14 w-14 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center shrink-0 group-hover:bg-blue-700 group-hover:text-white transition-colors duration-300">
                                <i class="ph-duotone ph-storefront text-3xl"></i>
                            </div>
                            <div class="pt-1">
                                <h2 class="text-lg font-bold text-slate-900 group-hover:text-blue-800 transition-colors">Portal Pemohon</h2>
                                <p class="text-sm text-slate-500 mt-1 leading-relaxed">Akses untuk Pelaku Usaha (PBF) mengajukan dan memantau denah.</p>
                            </div>
                            <div class="absolute right-6 top-1/2 -translate-y-1/2 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300 text-blue-600">
                                <i class="ph-bold ph-arrow-right text-xl"></i>
                            </div>
                        </div>
                    </a>
                </div>

                {{-- Demo Credentials --}}
                <div class="relative">
                    <div class="absolute -inset-x-4 -inset-y-4 z-0 bg-slate-100/50 rounded-3xl border border-slate-200/50 border-dashed hidden sm:block"></div>
                    <div class="relative z-10 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        
                        {{-- Akun Internal --}}
                        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                            <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-100">
                                <i class="ph-fill ph-users-three text-emerald-600 text-lg"></i>
                                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Akun Demo Internal</h3>
                            </div>
                            <div class="space-y-2.5 text-[13px] font-mono">
                                <div class="flex justify-between items-center"><span class="text-slate-400">K. Balai</span><span class="text-slate-700 font-medium">kepala.balai@bbpom.test</span></div>
                                <div class="flex justify-between items-center"><span class="text-slate-400">K. Tim</span><span class="text-slate-700 font-medium">ketua.tim@bbpom.test</span></div>
                                <div class="flex justify-between items-center"><span class="text-slate-400">Staff 1</span><span class="text-slate-700 font-medium">staff1@bbpom.test</span></div>
                                <div class="flex justify-between items-center"><span class="text-slate-400">Admin</span><span class="text-slate-700 font-medium">admin.it@bbpom.test</span></div>
                                <div class="pt-2 mt-3 border-t border-slate-100 flex items-center justify-between">
                                    <span class="text-slate-500 font-sans text-xs flex items-center gap-1.5"><i class="ph-fill ph-lock-key text-slate-400"></i> Password</span> 
                                    <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded font-bold">password</span>
                                </div>
                            </div>
                        </div>

                        {{-- Akun Pemohon --}}
                        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                            <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-100">
                                <i class="ph-fill ph-identification-card text-blue-600 text-lg"></i>
                                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Akun Demo Pemohon</h3>
                            </div>
                            <div class="space-y-2.5 text-[13px] font-mono">
                                <div class="flex justify-between items-center"><span class="text-slate-400">Email</span><span class="text-slate-700 font-medium">pemohon@contohfarma.test</span></div>
                                <div class="flex justify-between items-center"><span class="text-slate-400">WhatsApp</span><span class="text-slate-700 font-medium">081234567890</span></div>
                                <div class="pt-2 mt-3 border-t border-slate-100 flex items-center justify-between">
                                    <span class="text-slate-500 font-sans text-xs flex items-center gap-1.5"><i class="ph-fill ph-lock-key text-slate-400"></i> Password</span> 
                                    <span class="text-blue-700 bg-blue-50 px-2 py-0.5 rounded font-bold">password</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <footer class="py-6 text-center text-xs text-slate-400 font-medium relative z-10">
            © {{ date('Y') }} BBPOM — Sistem Pengesahan Denah PBF
        </footer>
    </div>
</body>
</html>