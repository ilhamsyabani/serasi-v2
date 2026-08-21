<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Portal Pelaku Usaha</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.head-assets')
</head>
<body class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-teal-50 flex items-center justify-center p-4" x-data="{ showPass: false }">
    @if($errors->has('identifier'))
        <div x-data x-init="
            Swal.fire({
                icon: 'error',
                title: 'Login Gagal',
                text: @js($errors->first('identifier')),
                confirmButtonColor: '#166534'
            });
        "></div>
    @endif
    <div class="w-full max-w-sm">
        {{-- Logo Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center h-14 w-14 rounded-2xl bg-emerald-600 text-white font-bold text-lg mb-4 shadow-lg shadow-emerald-600/30">
                <i class="ph-fill ph-first-aid-kit text-2xl" aria-hidden="true"></i>
            </div>
            <h1 class="text-xl font-bold text-slate-900">Portal Pelaku Usaha</h1>
            <p class="text-sm text-slate-500 mt-1">Pengajuan Denah PBF</p>
        </div>

        {{-- Login Card --}}
        <div class="bg-white rounded-2xl shadow-xl shadow-emerald-200/50 border border-emerald-100 p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="h-9 w-9 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                    <i class="ph ph-buildings text-emerald-600 text-lg" aria-hidden="true"></i>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Masuk</h2>
                    <p class="text-xs text-slate-500">Login dengan Email atau No. WhatsApp</p>
                </div>
            </div>

            @if(session('success'))
                <x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>
            @endif
            @if(session('error'))
                <x-ui.alert type="error" class="mb-4">{{ session('error') }}</x-ui.alert>
            @endif
            @if(session('info'))
                <x-ui.alert type="info" class="mb-4">{{ session('info') }}</x-ui.alert>
            @endif
            @if(session('otp_required'))
                <x-ui.alert type="warning" class="mb-4">Akun memerlukan verifikasi OTP. Silakan hubungi Admin IT.</x-ui.alert>
            @endif

            <form method="POST" action="{{ route('pemohon.login.submit') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="identifier" class="block text-xs font-medium text-slate-600 mb-1.5">Email atau No. WhatsApp</label>
                    <input type="text" id="identifier" name="identifier" value="{{ old('identifier') }}" placeholder="email@pbf.id atau 08xxxxxxxxxx" required autofocus
                           class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-white text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-colors">
                </div>
                <div>
                    <label for="password" class="block text-xs font-medium text-slate-600 mb-1.5">Password</label>
                    <div class="relative">
                        <input :type="showPass ? 'text' : 'password'" id="password" name="password" placeholder="••••••••" required
                               class="w-full h-10 px-3 pr-10 rounded-lg border border-slate-200 bg-white text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-colors">
                        <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors" aria-label="Tampilkan sembunyikan password">
                            <i x-show="!showPass" class="ph ph-eye text-base" aria-hidden="true"></i>
                            <i x-show="showPass" x-cloak class="ph ph-eye-slash text-base" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <button type="submit"
                        class="w-full h-10 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors flex items-center justify-center gap-2">
                    <i class="ph ph-sign-in text-lg" aria-hidden="true"></i>
                    Masuk
                </button>
                <div class="text-center">
                    <a href="{{ route('pemohon.password.request') }}" class="text-xs text-emerald-600 hover:text-emerald-800 hover:underline">Lupa password?</a>
                </div>
                <div class="text-center">
                    <a href="{{ route('home') }}" class="text-xs text-slate-400 hover:text-slate-600 hover:underline">Kembali ke Beranda</a>
                </div>
        
            </form>
            
        </div>
    

        <p class="text-center text-xs text-slate-400 mt-6">
            <a href="{{ route('internal.login') }}" class="hover:text-emerald-700 hover:underline transition-colors flex items-center justify-center gap-1.5">
            </a>
        </p>
    </div>
</body>
</html>
