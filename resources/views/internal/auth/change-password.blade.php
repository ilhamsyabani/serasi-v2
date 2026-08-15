@php $pageTitle = 'Ubah Password'; @endphp
@extends('layouts.internal')

@php $user = Auth::user(); @endphp

@section('content')
<div class="max-w-lg">
    <x-ui.card>
        <x-ui.card-header>
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                    <i class="ph ph-lock text-emerald-600 text-lg" aria-hidden="true"></i>
                </div>
                <div>
                    <h1 class="text-base font-semibold text-slate-900">Ubah Password</h1>
                    <p class="text-xs text-slate-500">Minimal 8 karakter dengan huruf dan angka</p>
                </div>
            </div>
        </x-ui.card-header>
        <x-ui.card-content>
            @if(session('error'))
                <x-ui.alert type="error" class="mb-4">{{ session('error') }}</x-ui.alert>
            @endif

            <form method="POST" action="{{ route('internal.password.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <x-ui.input
                    label="Password Saat Ini"
                    name="current_password"
                    type="password"
                    placeholder="Masukkan password saat ini"
                    required
                    autocomplete="current-password"
                    :error="$errors->first('current_password')"
                    toggle="true"
                />

                <x-ui.input
                    label="Password Baru"
                    name="password"
                    type="password"
                    placeholder="Minimal 8 karakter"
                    required
                    autocomplete="new-password"
                    :error="$errors->first('password')"
                    toggle="true"
                />

                <x-ui.input
                    label="Konfirmasi Password Baru"
                    name="password_confirmation"
                    type="password"
                    placeholder="Masukkan ulang password baru"
                    required
                    autocomplete="new-password"
                    :error="$errors->first('password_confirmation')"
                    toggle="true"
                />

                <div class="flex items-center gap-3 pt-2">
                    <x-ui.button type="submit" variant="default" size="md">
                        <i class="ph ph-floppy-disk mr-1.5" aria-hidden="true"></i>
                        Simpan Password
                    </x-ui.button>
                    <a href="{{ url()->previous() }}" class="text-sm text-slate-500 hover:text-slate-700 px-3 py-2">
                        Batal
                    </a>
                </div>
            </form>
        </x-ui.card-content>
    </x-ui.card>
</div>
@endsection
