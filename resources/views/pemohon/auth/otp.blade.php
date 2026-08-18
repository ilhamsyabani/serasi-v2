<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP — Portal Pemohon</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.head-assets')
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        {{-- Logo Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center h-14 w-14 rounded-2xl bg-emerald-800 text-white font-bold text-lg mb-4">BP</div>
            <h1 class="text-xl font-bold text-slate-900">Verifikasi Akun</h1>
            <p class="text-sm text-slate-500 mt-1">Portal Pelaku Usaha</p>
        </div>

        <x-ui.card>
            <x-ui.card-content>
                <p class="text-sm text-slate-600 mb-6">
                    Kode OTP telah dikirim ke WhatsApp <strong>{{ substr($pbf->no_whatsapp, 0, 4) }}****{{ substr($pbf->no_whatsapp, -4) }}</strong>.
                </p>

                @if(session('success'))
                    <x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>
                @endif

                @if($errors->has('kode'))
                    <div x-data x-init="
                        Swal.fire({
                            icon: 'error',
                            title: 'OTP Salah',
                            text: @js($errors->first('kode')),
                            confirmButtonColor: '#166534'
                        });
                    "></div>
                @endif

                <form method="POST" action="{{ route('pemohon.otp.verify') }}" class="space-y-4">
                    @csrf
                    <x-ui.input
                        label="Kode OTP"
                        name="kode"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        placeholder="000000"
                        autocomplete="one-time-code"
                        required
                        autofocus
                    />
                    <x-ui.button type="submit" variant="default" class="w-full" size="full">Verifikasi</x-ui.button>
                </form>

                <form method="POST" action="{{ route('pemohon.otp.resend') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="w-full text-center text-sm text-emerald-700 hover:text-emerald-800 font-medium py-1">
                        Kirim Ulang Kode OTP
                    </button>
                </form>
            </x-ui.card-content>
        </x-ui.card>

        <div class="text-center mt-6">
            <form method="POST" action="{{ route('pemohon.logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-sm text-slate-400 hover:text-slate-600">
                    ← Batal dan login ulang
                </button>
            </form>
        </div>
    </div>
</body>
</html>
