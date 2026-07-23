@extends('layouts.internal')

@section('title', 'Edit User')
@section('content')
<?php $pageTitle = 'Edit User'; ?>

<x-ui.card>
    <x-ui.card-header title="Edit User" description="Ubah informasi akun internal" />
    <x-ui.card-content>
        <form method="POST" action="{{ route('internal.adminit.users.update', $user) }}" class="space-y-5 max-w-lg">
            @csrf @method('PUT')
            <x-ui.input label="Nama Lengkap" name="nama" :value="old('nama', $user->nama)" required />
            <x-ui.input label="NIP" name="nip" :value="old('nip', $user->nip)" placeholder="198501012010011001" required />
            <x-ui.input label="Email" name="email" type="email" :value="old('email', $user->email)" required />
            <x-ui.select label="Role" name="role_id" :options="$roles->mapWithKeys(fn($r) => [$r->id => $r->nama])->toArray()" :required="true" />
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_aktif" value="1" class="h-4 w-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-700" {{ $user->is_aktif ? 'checked' : '' }}>
                    <span class="text-sm text-slate-700">Akun aktif</span>
                </label>
            </div>
            <x-ui.input label="Password Baru (kosongkan jika tidak diubah)" name="password" type="password" placeholder="Min. 8 karakter" />
            <div class="flex items-center gap-3 pt-2">
                <x-ui.button type="submit" variant="default">Simpan</x-ui.button>
                <x-ui.button variant="outline" href="{{ route('internal.adminit.users.index') }}">Batal</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
@endsection
