<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password — Portal Internal BBPOM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.head-assets')
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center h-14 w-14 rounded-2xl bg-emerald-800 text-white font-bold text-lg mb-4">BP</div>
            <h1 class="text-xl font-bold text-slate-900">Lupa Password</h1>
            <p class="text-sm text-slate-500 mt-1">Portal Internal BBPOM</p>
        </div>

        <x-ui.card>
            <x-ui.card-content>
                <p class="text-sm text-slate-600 mb-6">
                    Masukkan alamat email akun Anda. Kami akan mengirimkan tautan untuk mereset password.
                </p>

                @if(session('success'))
                    <x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>
                @endif

                <form method="POST" action="{{ route('internal.password.email') }}" class="space-y-4">
                    @csrf
                    <x-ui.input label="Email" name="email" type="email" :value="old('email')" placeholder="email@bbpom.go.id" required autofocus />
                    <x-ui.button type="submit" variant="default" size="full">Kirim Tautan Reset</x-ui.button>
                </form>

                <div class="mt-4 text-center">
                    <a href="{{ route('internal.login') }}" class="text-xs text-emerald-600 hover:text-emerald-700 hover:underline">
                        ← Kembali ke halaman login
                    </a>
                </div>
            </x-ui.card-content>
        </x-ui.card>
    </div>
</body>
</html>
