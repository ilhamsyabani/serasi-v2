<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Password — Portal Pemohon</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.head-assets')
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        {{-- Logo Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center h-14 w-14 rounded-2xl bg-emerald-800 text-white font-bold text-lg mb-4">BP</div>
            <h1 class="text-xl font-bold text-slate-900">Ubah Password</h1>
            <p class="text-sm text-slate-500 mt-1">Portal Pelaku Usaha</p>
        </div>

        <x-ui.card>
            <x-ui.card-content>
                @if(session('success'))
                    <x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>
                @endif
                @if(session('error'))
                    <x-ui.alert type="error" class="mb-4">{{ session('error') }}</x-ui.alert>
                @endif
                @if($errors->any())
                    <x-ui.alert type="error" class="mb-4">{{ $errors->first() }}</x-ui.alert>
                @endif

                <form method="POST" action="{{ route('pemohon.password.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <x-ui.input
                        label="Password Saat Ini"
                        name="current_password"
                        type="password"
                        placeholder="Masukkan password saat ini"
                        required
                        autocomplete="current-password"
                        toggle="true"
                    />

                    <x-ui.input
                        label="Password Baru"
                        name="password"
                        type="password"
                        placeholder="Minimal 8 karakter"
                        required
                        autocomplete="new-password"
                        toggle="true"
                    />

                    <x-ui.input
                        label="Konfirmasi Password Baru"
                        name="password_confirmation"
                        type="password"
                        placeholder="Masukkan ulang password baru"
                        required
                        autocomplete="new-password"
                        toggle="true"
                    />

                    <x-ui.button type="submit" variant="default" class="w-full" size="full">
                        <i class="ph ph-floppy-disk mr-1.5" aria-hidden="true"></i>
                        Simpan Password
                    </x-ui.button>

                    <div class="text-center">
                        <a href="{{ route('pemohon.dashboard') }}" class="text-xs text-slate-400 hover:text-slate-600 hover:underline">
                            ← Kembali ke Dashboard
                        </a>
                    </div>
                </form>
            </x-ui.card-content>
        </x-ui.card>
    </div>
</body>
</html>
