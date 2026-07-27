<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Persetujuan Denah PBF {{ config('app.name', 'BBPOM') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased selection:bg-emerald-200 selection:text-emerald-900 font-sans">

    {{-- Navbar --}}
    <nav class="fixed top-0 inset-x-0 z-50 bg-white/80 backdrop-blur-md border-b border-slate-200/60">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-8 w-8">
                    <x-ui.logo src="img/logos.png" alt="Logo" />
                </div>
                <span class="font-bold text-slate-900 tracking-tight">Serasi</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="#portal" class="hidden sm:block text-sm font-medium text-slate-500 hover:text-slate-900 transition-colors">Portal Login</a>
                <a href="{{ route('pemohon.login') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-sm shadow-emerald-600/20 transition-all">
                    Masuk / Daftar
                </a>
            </div>
        </div>
    </nav>

    {{-- Hero Section --}}
    <section class="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden">
        {{-- Background Decoration --}}
        <div class="absolute top-0 right-0 -translate-y-12 translate-x-1/3">
            <div class="w-96 h-96 bg-emerald-100/50 rounded-full blur-3xl"></div>
        </div>
        <div class="absolute bottom-0 left-0 translate-y-1/3 -translate-x-1/3">
            <div class="w-[30rem] h-[30rem] bg-blue-50/50 rounded-full blur-3xl"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-6 grid lg:grid-cols-2 gap-12 items-center">
            <div class="max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-semibold uppercase tracking-wider mb-6">
                    <span class="relative flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    Layanan Elektronik BBPOM
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight mb-6 leading-[1.15]">
                    Pengesahan Denah PBF <span class="text-transparent bg-clip-text bg-emerald-600">Lebih Cepat & <span class="text-transparent bg-clip-text bg-blue-600">
                    Mudah</span></span>
                </h1>
                <p class="text-lg text-slate-600 mb-8 leading-relaxed">
                    Sistem resmi Balai Besar POM untuk memfasilitasi pelaku usaha farmasi (Pedagang Besar Farmasi) dalam mengajukan evaluasi dan persetujuan denah bangunan secara *online* dan transparan.
                </p>
                <div class="flex flex-wrap items-center gap-4">
                    <a href="{{ route('pemohon.login') }}" class="inline-flex justify-center items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold px-6 py-3.5 rounded-xl shadow-lg shadow-slate-900/20 transition-all">
                        Mulai Pengajuan <i class="ph-bold ph-arrow-right"></i>
                    </a>
                    <a href="#alur" class="inline-flex justify-center items-center gap-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 font-semibold px-6 py-3.5 rounded-xl transition-all">
                        Lihat Alur <i class="ph-bold ph-caret-down"></i>
                    </a>
                </div>
            </div>

            {{-- Hero Graphic / Dashboard Mockup Illustration --}}
            <div class="relative hidden lg:block">
                <div class="relative z-10 bg-white rounded-2xl shadow-2xl shadow-slate-200/50 border border-slate-200/60 p-2 overflow-hidden transform rotate-2 hover:rotate-0 transition-transform duration-500">
                    <div class="bg-slate-50 rounded-xl border border-slate-100 overflow-hidden h-80 flex flex-col">
                        <div class="h-10 bg-white border-b border-slate-100 flex items-center px-4 gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                            <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                        </div>
                        <div class="p-6 flex-1 flex flex-col gap-4">
                            <div class="h-8 w-1/3 bg-slate-200 rounded-lg"></div>
                            <div class="flex gap-4">
                                <div class="h-24 w-1/4 bg-blue-100 rounded-xl"></div>
                                <div class="h-24 w-1/4 bg-emerald-100 rounded-xl"></div>
                                <div class="h-24 w-1/4 bg-amber-100 rounded-xl"></div>
                            </div>
                            <div class="flex-1 bg-white border border-slate-100 rounded-xl mt-2 p-4">
                                <div class="h-4 w-1/4 bg-slate-200 rounded mb-3"></div>
                                <div class="h-3 w-full bg-slate-100 rounded mb-2"></div>
                                <div class="h-3 w-5/6 bg-slate-100 rounded mb-2"></div>
                                <div class="h-3 w-4/6 bg-slate-100 rounded"></div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Floating Badge --}}
                <div class="absolute -bottom-6 -left-6 z-20 bg-white rounded-2xl shadow-xl shadow-emerald-900/10 border border-emerald-100 p-4 flex items-center gap-4 animate-bounce" style="animation-duration: 3s;">
                    <div class="h-12 w-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                        <i class="ph-fill ph-check-circle text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800">Telah Disahkan</p>
                        <p class="text-xs text-slate-500">Oleh Kepala Balai</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Keunggulan Section --}}
    <section class="py-20 bg-white border-y border-slate-200/60">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <h2 class="text-3xl font-bold text-slate-900 mb-4">Mengapa Menggunakan Sistem Ini?</h2>
                <p class="text-slate-500">Kami berkomitmen memberikan pelayanan publik yang prima, transparan, dan akuntabel bagi seluruh pelaku usaha farmasi.</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-slate-50 rounded-2xl p-8 border border-slate-100">
                    <i class="ph-duotone ph-rocket-launch text-5xl text-blue-600 mb-6"></i>
                    <h3 class="text-xl font-bold text-slate-800 mb-3">Efisien & Paperless</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Tidak perlu datang ke kantor balai. Unggah denah dan dokumen pendukung dari mana saja, kapan saja.</p>
                </div>
                <div class="bg-slate-50 rounded-2xl p-8 border border-slate-100">
                    <i class="ph-duotone ph-crosshair text-5xl text-emerald-600 mb-6"></i>
                    <h3 class="text-xl font-bold text-slate-800 mb-3">Lacak Real-time</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Pantau status permohonan Anda secara *real-time* dengan timeline yang jelas dari tahap evaluasi hingga selesai.</p>
                </div>
                <div class="bg-slate-50 rounded-2xl p-8 border border-slate-100">
                    <i class="ph-duotone ph-shield-check text-5xl text-amber-500 mb-6"></i>
                    <h3 class="text-xl font-bold text-slate-800 mb-3">Resmi & Aman</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Surat pengesahan diterbitkan secara elektronik dengan tingkat keamanan tinggi dan tersimpan aman di sistem.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Alur Pengajuan --}}
    <section id="alur" class="py-40">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-slate-900 text-center mb-16">Alur Pengajuan Denah</h2>
            
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="relative">
                    <div class="text-6xl font-black text-slate-100 absolute -top-8 -left-4 z-0">01</div>
                    <div class="relative z-10">
                        <i class="ph-fill ph-user-circle text-4xl text-slate-700 mb-4"></i>
                        <h4 class="text-lg font-bold text-slate-900 mb-2">Registrasi Akun</h4>
                        <p class="text-sm text-slate-500">Buat akun perusahaan Anda menggunakan email aktif dan isi profil PBF.</p>
                    </div>
                </div>
                <div class="relative">
                    <div class="text-6xl font-black text-slate-100 absolute -top-8 -left-4 z-0">02</div>
                    <div class="relative z-10">
                        <i class="ph-fill ph-file-arrow-up text-4xl text-blue-600 mb-4"></i>
                        <h4 class="text-lg font-bold text-slate-900 mb-2">Unggah Dokumen</h4>
                        <p class="text-sm text-slate-500">Lengkapi formulir permohonan dan unggah file denah gudang PBF Anda.</p>
                    </div>
                </div>
                <div class="relative">
                    <div class="text-6xl font-black text-slate-100 absolute -top-8 -left-4 z-0">03</div>
                    <div class="relative z-10">
                        <i class="ph-fill ph-magnifying-glass text-4xl text-amber-500 mb-4"></i>
                        <h4 class="text-lg font-bold text-slate-900 mb-2">Proses Evaluasi</h4>
                        <p class="text-sm text-slate-500">Tim evaluator akan meninjau kesesuaian denah. Lakukan revisi jika diminta.</p>
                    </div>
                </div>
                <div class="relative">
                    <div class="text-6xl font-black text-slate-100 absolute -top-8 -left-4 z-0">04</div>
                    <div class="relative z-10">
                        <i class="ph-fill ph-seal-check text-4xl text-emerald-600 mb-4"></i>
                        <h4 class="text-lg font-bold text-slate-900 mb-2">Terbit Pengesahan</h4>
                        <p class="text-sm text-slate-500">Unduh Surat Pengesahan Denah secara langsung melalui dashboard Anda.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Akses Portal & Akun Demo Section --}}
    <section id="portal" class="py-20 bg-slate-900 text-white relative overflow-hidden">
        {{-- Inner subtle gradient --}}
        <div class="absolute inset-0 bg-gradient-to-b from-transparent to-slate-950/80"></div>
        
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold mb-4">Masuk ke Portal</h2>
                <p class="text-slate-400">Pilih akses masuk yang sesuai dengan peran Anda.</p>
            </div>

            <div class="grid md:grid-cols-2 gap-6 max-w-4xl mx-auto mb-16">
                {{-- Portal Pemohon (Primary) --}}
                <a href="{{ route('pemohon.login') }}" class="group bg-slate-800/50 backdrop-blur-sm border border-slate-700 p-6 rounded-2xl hover:bg-slate-800 hover:border-emerald-500 transition-all text-left flex items-center justify-between">
                    <div class="flex items-center gap-5">
                        <div class="h-14 w-14 rounded-full bg-emerald-900/50 text-emerald-400 flex items-center justify-center shrink-0">
                            <i class="ph-fill ph-storefront text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-100 group-hover:text-emerald-400 transition-colors">Portal Pemohon</h3>
                            <p class="text-sm text-slate-400 mt-1">Untuk Pelaku Usaha / PBF</p>
                        </div>
                    </div>
                    <i class="ph-bold ph-arrow-right text-slate-600 group-hover:text-emerald-400 group-hover:translate-x-1 transition-all"></i>
                </a>

                {{-- Portal Internal (Secondary) --}}
                <a href="{{ route('internal.login') }}" class="group bg-slate-800/50 backdrop-blur-sm border border-slate-700 p-6 rounded-2xl hover:bg-slate-800 hover:border-blue-500 transition-all text-left flex items-center justify-between">
                    <div class="flex items-center gap-5">
                        <div class="h-14 w-14 rounded-full bg-blue-900/50 text-blue-400 flex items-center justify-center shrink-0">
                            <i class="ph-fill ph-shield-check text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-100 group-hover:text-blue-400 transition-colors">Portal Internal</h3>
                            <p class="text-sm text-slate-400 mt-1">Khusus Pegawai BBPOM</p>
                        </div>
                    </div>
                    <i class="ph-bold ph-arrow-right text-slate-600 group-hover:text-blue-400 group-hover:translate-x-1 transition-all"></i>
                </a>
            </div>

            {{-- Akun Demo --}}
            <div class="max-w-4xl mx-auto border border-slate-700/50 rounded-2xl overflow-hidden bg-slate-900/30">
                <div class="bg-slate-800/80 px-6 py-4 border-b border-slate-700/50 flex items-center gap-3">
                    <i class="ph-fill ph-info text-slate-400"></i>
                    <h3 class="font-semibold text-slate-300 text-sm">Informasi Akun Demo (Development Mode)</h3>
                </div>
                <div class="p-6 grid sm:grid-cols-2 gap-8">
                    <div>
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 border-b border-slate-700 pb-2">Demo Pemohon</h4>
                        <div class="space-y-3 text-sm font-mono text-slate-300">
                            <div class="flex justify-between"><span>Email</span><span class="text-white">pemohon@contohfarma.test</span></div>
                            <div class="flex justify-between"><span>WhatsApp</span><span class="text-white">081234567890</span></div>
                            <div class="flex justify-between text-emerald-400 pt-2 border-t border-slate-700/50"><span>Password</span><span>password</span></div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 border-b border-slate-700 pb-2">Demo Internal BBPOM</h4>
                        <div class="space-y-3 text-sm font-mono text-slate-300">
                            <div class="flex justify-between"><span>K. Balai</span><span class="text-white">198501012010011001</span></div>
                            <div class="flex justify-between"><span>K. Tim</span><span class="text-white">198703152011012002</span></div>
                            <div class="flex justify-between"><span>Staff</span><span class="text-white">199002102015031003</span></div>
                            <div class="flex justify-between text-blue-400 pt-2 border-t border-slate-700/50"><span>Password Semua</span><span>password</span></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-white py-8 border-t border-slate-200 text-center">
        <p class="text-sm text-slate-500 font-medium">
            © {{ date('Y') }} Balai Besar Pengawasan Obat dan Makanan (BBPOM).<br class="sm:hidden" /> Hak Cipta Dilindungi Undang-Undang.
        </p>
    </footer>

</body>
</html>