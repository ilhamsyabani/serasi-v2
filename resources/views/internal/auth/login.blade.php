<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Portal Internal BBPOM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.head-assets')
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-blue-950 flex items-center justify-center p-4" x-data="{ showPass: false }">
    <div class="w-full max-w-md">
        {{-- Logo Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center h-14 w-14 rounded-2xl bg-white text-blue-900 font-bold text-lg mb-4 shadow-lg">
                <i class="ph-fill ph-first-aid-kit text-2xl" aria-hidden="true"></i>
            </div>
            <h1 class="text-xl font-bold text-white">Portal Internal BBPOM</h1>
            <p class="text-sm text-blue-200 mt-1">Pengesahan Denah PBF</p>
        </div>

        {{-- Login Card --}}
        <div class="bg-white rounded-2xl shadow-xl shadow-blue-900/30 p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="h-9 w-9 rounded-lg bg-blue-100 flex items-center justify-center shrink-0">
                    <i class="ph ph-user-circle text-blue-600 text-lg" aria-hidden="true"></i>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Masuk</h2>
                    <p class="text-xs text-slate-500">Autentikasi via SSO BPOM</p>
                </div>
            </div>

            @isset($errors)
                @if($errors->any())
                    <div x-data x-init="
                        Swal.fire({
                            icon: 'error',
                            title: 'Login Gagal',
                            text: @js($errors->first()),
                            confirmButtonColor: '#1e3a8a'
                        });
                    "></div>
                @endif
            @endisset

            <form method="POST" action="{{ route('internal.login.submit') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="nip" class="block text-xs font-medium text-slate-600 mb-1.5">NIP</label>
                    <input type="text" id="nip" name="nip" value="{{ old('nip') }}" placeholder="198501012010011001" required autofocus
                           class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-colors">
                </div>
                <div>
                    <label for="password" class="block text-xs font-medium text-slate-600 mb-1.5">Password</label>
                    <div class="relative">
                        <input :type="showPass ? 'text' : 'password'" id="password" name="password" placeholder="••••••••" required
                               class="w-full h-10 px-3 pr-10 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-colors">
                        <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors" aria-label="Tampilkan sembunyikan password">
                            <i x-show="!showPass" class="ph ph-eye text-base" aria-hidden="true"></i>
                            <i x-show="showPass" x-cloak class="ph ph-eye-slash text-base" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-xs text-slate-600 select-none">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                           class="h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                    Ingat saya di perangkat ini
                </label>
                <button type="submit"
                        class="w-full h-10 bg-blue-900 hover:bg-blue-800 text-white text-sm font-semibold rounded-lg transition-colors flex items-center justify-center gap-2">
                    <i class="ph ph-sign-in text-lg" aria-hidden="true"></i>
                    Masuk
                </button>
               
            </form>
        </div>

        <p class="text-center text-xs text-blue-300 mt-6">
            <a href="{{ route('home') }}" class="hover:text-white transition-colors flex items-center justify-center gap-1.5">
                <i class="ph ph-arrow-right" aria-hidden="true"></i>
                Back to Home
            </a>
        </p>
    </div>
</body>
</html>
