@extends('layouts.internal')

@section('title', 'Tambah User')
@section('content')
<?php $pageTitle = 'Tambah User'; ?>

<div class="mb-6">
    <h2 class="text-lg font-semibold text-slate-900">Tambah User Baru</h2>
    <p class="text-sm text-slate-500">Buat akun internal BBPOM</p>
</div>

@if($errors->any())
    <x-ui.alert type="error" class="mb-5">Periksa kembali isian: {{ $errors->first() }}</x-ui.alert>
@endif

<x-ui.card>
    <x-ui.card-header title="Data User" description="Field bertanda (*) wajib diisi" />
    <x-ui.card-content>
        <form method="POST" action="{{ route('internal.adminit.users.store') }}" class="space-y-5 max-w-lg">
            @csrf

            <x-ui.input label="Nama Lengkap" name="nama" :value="old('nama')" placeholder="Nama lengkap" :error="$errors->first('nama')" required />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-ui.input label="NIP" name="nip" :value="old('nip')" placeholder="198501012010011001" :error="$errors->first('nip')" required />
                <x-ui.input label="Email" name="email" type="email" :value="old('email')" placeholder="email@bbpom.id" :error="$errors->first('email')" required />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-ui.select label="Role" name="role_id" :options="$roles->mapWithKeys(fn($r) => [$r->id => $r->nama])->toArray()" :selected="old('role_id')" :required="true" :error="$errors->first('role_id')" />
                <x-ui.input label="No. WhatsApp" name="no_whatsapp" type="text" :value="old('no_whatsapp')" placeholder="08xxxxxxxxxx" :error="$errors->first('no_whatsapp')" />
            </div>

            <x-ui.input label="Password" name="password" type="password" placeholder="Min. 8 karakter" :error="$errors->first('password')" required />

            <div class="flex items-center gap-3 pt-2">
                <x-ui.button type="submit" variant="default">Simpan</x-ui.button>
                <x-ui.button variant="outline" href="{{ route('internal.adminit.users.index') }}">Batal</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
@endsection
