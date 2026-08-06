<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Portal Pemohon</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.head-assets')
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        {{-- Logo Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center h-14 w-14 rounded-2xl bg-emerald-800 text-white font-bold text-lg mb-4">BP</div>
            <h1 class="text-xl font-bold text-slate-900">Portal Pelaku Usaha</h1>
            <p class="text-sm text-slate-500 mt-1">Pengajuan Denah PBF</p>
        </div>

        {{-- Login Card --}}
        <x-ui.card>
            <x-ui.card-content>
                <h2 class="text-base font-semibold text-slate-900 mb-1">Masuk</h2>
                <p class="text-xs text-slate-500 mb-6">Login dengan Email atau No. WhatsApp</p>

                @if(session('info'))
                    <x-ui.alert type="info" class="mb-4">{{ session('info') }}</x-ui.alert>
                @endif
                @if(session('success'))
                    <x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>
                @endif
                @if(session('error'))
                    <x-ui.alert type="error" class="mb-4">{{ session('error') }}</x-ui.alert>
                @endif
                @if(session('otp_required'))
                    <x-ui.alert type="warning" class="mb-4">Akun memerlukan verifikasi OTP. Silakan hubungi Admin IT.</x-ui.alert>
                @endif

                <form method="POST" action="{{ route('pemohon.login.submit') }}" class="space-y-4">
                    @csrf
                    <x-ui.input label="Email atau No. WhatsApp" name="identifier" :value="old('identifier')" placeholder="email@pbf.id atau 08xxxxxxxxxx" required autofocus />
                    <x-ui.input label="Password" name="password" type="password" placeholder="••••••••" required />
                    <x-ui.button type="submit" variant="default" class="w-full" size="full">Masuk</x-ui.button>
                    <div class="text-center">
                        <a href="{{ route('pemohon.password.request') }}" class="text-xs text-emerald-600 hover:text-emerald-700 hover:underline">Lupa password?</a>
                    </div>
                </form>
            </x-ui.card-content>
        </x-ui.card>

        <p class="text-center text-xs text-slate-400 mt-6">
            <a href="{{ route('internal.login') }}" class="hover:text-slate-600">← Portal Internal BBPOM</a>
        </p>
    </div>
</body>
</html>
