@extends('layouts.internal')

@section('title', 'Tambah User')
@section('content')
<?php $pageTitle = 'Tambah User'; ?>

<x-ui.card>
    <x-ui.card-header title="Tambah User Baru" description="Buat akun internal BBPOM" />
    <x-ui.card-content>
        <form method="POST" action="{{ route('internal.adminit.users.store') }}" class="space-y-5 max-w-lg">
            @csrf
            <x-ui.input label="Nama Lengkap" name="nama" :value="old('nama')" placeholder="Nama lengkap" required />
            <x-ui.input label="NIP" name="nip" :value="old('nip')" placeholder="198501012010011001" required />
            <x-ui.input label="Email" name="email" type="email" :value="old('email')" placeholder="email@bbpom.id" required />
            <x-ui.select label="Role" name="role_id" :options="$roles->mapWithKeys(fn($r) => [$r->id => $r->nama])->toArray()" :required="true" />
            <x-ui.input label="Password" name="password" type="password" placeholder="Min. 8 karakter" required />
            <div class="flex items-center gap-3 pt-2">
                <x-ui.button type="submit" variant="default">Simpan</x-ui.button>
                <x-ui.button variant="outline" href="{{ route('internal.adminit.users.index') }}">Batal</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
@endsection
