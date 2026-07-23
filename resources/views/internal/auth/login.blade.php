<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Portal Internal BBPOM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.head-assets')
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        {{-- Logo Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center h-14 w-14 rounded-2xl bg-emerald-800 text-white font-bold text-lg mb-4">BP</div>
            <h1 class="text-xl font-bold text-slate-900">Portal Internal BBPOM</h1>
            <p class="text-sm text-slate-500 mt-1">Pengesahan Denah PBF</p>
        </div>

        {{-- Login Card --}}
        <x-ui.card>
            <x-ui.card-content>
                <h2 class="text-base font-semibold text-slate-900 mb-1">Masuk</h2>
                <p class="text-xs text-slate-500 mb-6">Autentikasi via SSO BPOM</p>

                @isset($errors)
                    @if($errors->any())
                        <x-ui.alert type="error" class="mb-4">{{ $errors->first() }}</x-ui.alert>
                    @endif
                @endisset

                <form method="POST" action="{{ route('internal.login.submit') }}" class="space-y-4">
                    @csrf
                    <x-ui.input label="NIP" name="nip" :value="old('nip')" placeholder="198501012010011001" required autofocus />
                    <x-ui.input label="Password" name="password" type="password" placeholder="••••••••" required />
                    <label class="flex items-center gap-2 text-xs text-slate-600 select-none">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                               class="h-4 w-4 rounded border-slate-300 text-emerald-800 focus:ring-emerald-700">
                        Ingat saya di perangkat ini
                    </label>
                    <x-ui.button type="submit" variant="default" class="w-full">Masuk</x-ui.button>
                </form>
            </x-ui.card-content>
        </x-ui.card>

        <p class="text-center text-xs text-slate-400 mt-6">
            <a href="{{ route('pemohon.login') }}" class="hover:text-slate-600">Portal Pemohon →</a>
        </p>
    </div>
</body>
</html>
