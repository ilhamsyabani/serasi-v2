@extends('layouts.internal')

@section('title', 'Edit Hari Libur')
@section('content')
<?php $pageTitle = 'Edit Hari Libur'; ?>

<div class="mb-6">
    <h2 class="text-lg font-semibold text-slate-900">Edit Hari Libur</h2>
    <p class="text-sm text-slate-500">Ubah data hari libur atau cuti</p>
</div>

@if($errors->any())
    <x-ui.alert type="error" class="mb-5">Periksa kembali isian: {{ $errors->first() }}</x-ui.alert>
@endif

<x-ui.card>
    <x-ui.card-header title="Data Hari Libur" description="Field bertanda (*) wajib diisi" />
    <x-ui.card-content>
        <form method="POST" action="{{ route('internal.adminit.hari-libur.update', $hariLibur) }}" class="space-y-5 max-w-md">
            @csrf @method('PUT')
            <x-ui.input label="Tanggal" name="tanggal" type="date" :value="old('tanggal', $hariLibur->tanggal->format('Y-m-d'))" :error="$errors->first('tanggal')" required />
            <x-ui.input label="Keterangan" name="keterangan" :value="old('keterangan', $hariLibur->keterangan)" placeholder="Libur nasional / Cuti bersama / ..." :error="$errors->first('keterangan')" required />
            <div class="flex items-center gap-3 pt-2">
                <x-ui.button type="submit" variant="default">Simpan</x-ui.button>
                <x-ui.button variant="outline" href="{{ route('internal.adminit.hari-libur.index') }}">Batal</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
@endsection
