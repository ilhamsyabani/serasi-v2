<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Portal Pemohon</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.head-assets')
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center h-14 w-14 rounded-2xl bg-emerald-800 text-white font-bold text-lg mb-4">BP</div>
            <h1 class="text-xl font-bold text-slate-900">Reset Password</h1>
            <p class="text-sm text-slate-500 mt-1">Portal Pelaku Usaha</p>
        </div>

        <x-ui.card>
            <x-ui.card-content>
                <p class="text-sm text-slate-600 mb-6">
                    Masukkan password baru untuk akun Anda.
                </p>

                @if($errors->has('kode_otp'))
                    <x-ui.alert type="error" class="mb-4">{{ $errors->first('kode_otp') }}</x-ui.alert>
                @endif
                @if($errors->has('token'))
                    <x-ui.alert type="error" class="mb-4">{{ $errors->first('token') }}</x-ui.alert>
                @endif

                <form method="POST" action="{{ route('pemohon.password.update') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <x-ui.input label="Password Baru" name="password" type="password" placeholder="Min. 8 karakter" required autofocus toggle="true" />
                    <x-ui.input label="Konfirmasi Password" name="password_confirmation" type="password" placeholder="Ulangi password baru" required toggle="true" />

                    <x-ui.button type="submit" variant="default" size="full">Reset Password</x-ui.button>
                </form>

                <div class="mt-4 text-center">
                    <a href="{{ route('pemohon.login') }}" class="text-xs text-emerald-600 hover:text-emerald-700 hover:underline">
                        ← Kembali ke halaman login
                    </a>
                </div>
            </x-ui.card-content>
        </x-ui.card>

        <p class="text-center text-xs text-slate-400 mt-6">
            <a href="{{ route('internal.login') }}" class="hover:text-slate-600">← Portal Internal BBPOM</a>
        </p>
    </div>
</body>
</html>
