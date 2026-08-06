<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Portal Internal BBPOM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.head-assets')
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center h-14 w-14 rounded-2xl bg-emerald-800 text-white font-bold text-lg mb-4">BP</div>
            <h1 class="text-xl font-bold text-slate-900">Reset Password</h1>
            <p class="text-sm text-slate-500 mt-1">Portal Internal BBPOM</p>
        </div>

        <x-ui.card>
            <x-ui.card-content>
                <form method="POST" action="{{ route('internal.password.update') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email }}">

                    <x-ui.input label="Email" name="email" type="email" :value="$email" placeholder="email@bbpom.go.id" required readonly />

                    <x-ui.input label="Password Baru" name="password" type="password" placeholder="Min. 8 karakter" required />
                    <x-ui.input label="Konfirmasi Password" name="password_confirmation" type="password" placeholder="Ulangi password baru" required />

                    <x-ui.button type="submit" variant="default" size="full">Reset Password</x-ui.button>
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
