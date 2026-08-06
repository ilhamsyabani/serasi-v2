@extends('layouts.internal')

@section('title', 'Tambah Hari Libur')
@section('content')
<?php $pageTitle = 'Tambah Hari Libur'; ?>

<div class="mb-6">
    <h2 class="text-lg font-semibold text-slate-900">Tambah Hari Libur</h2>
    <p class="text-sm text-slate-500">Tambahkan hari libur atau cuti untuk pengecualian perhitungan SLA</p>
</div>

@if($errors->any())
    <x-ui.alert type="error" class="mb-5">Periksa kembali isian: {{ $errors->first() }}</x-ui.alert>
@endif

<x-ui.card>
    <x-ui.card-header title="Data Hari Libur" description="Field bertanda (*) wajib diisi" />
    <x-ui.card-content>
        <form method="POST" action="{{ route('internal.adminit.hari-libur.store') }}" class="space-y-5 max-w-md">
            @csrf
            <x-ui.input label="Tanggal" name="tanggal" type="date" :value="old('tanggal')" :error="$errors->first('tanggal')" required />
            <x-ui.input label="Keterangan" name="keterangan" :value="old('keterangan')" placeholder="Libur nasional / Cuti bersama / ..." :error="$errors->first('keterangan')" required />
            <div class="flex items-center gap-3 pt-2">
                <x-ui.button type="submit" variant="default">Simpan</x-ui.button>
                <x-ui.button variant="outline" href="{{ route('internal.adminit.hari-libur.index') }}">Batal</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
@endsection
