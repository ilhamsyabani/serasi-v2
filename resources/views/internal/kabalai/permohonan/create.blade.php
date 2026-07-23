@extends('layouts.internal')

@section('title', 'Input Permohonan Baru')
@section('content')
<?php $pageTitle = 'Input Permohonan Baru'; ?>

<div class="mb-6">
    <h2 class="text-lg font-semibold text-slate-900">Input Permohonan Baru</h2>
    <p class="text-sm text-slate-500">Daftarkan permohonan rancangan denah PBF baru</p>
</div>

@php
    // Sumber kebenaran tunggal untuk daftar & label dokumen (lihat DokumenPermohonan::JENIS).
    $jenisDokumen = \App\Models\DokumenPermohonan::JENIS;
    $accept = '.' . implode(',.', \App\Models\DokumenPermohonan::EKSTENSI_DIIZINKAN);
@endphp

@if($errors->any())
    <x-ui.alert type="error" class="mb-5">Periksa kembali isian: {{ $errors->first() }}</x-ui.alert>
@endif

<x-ui.card>
    <x-ui.card-header title="Data Pemohon" description="Isi informasi PBF dan dokumen pendukung" />
    <x-ui.card-content>
        <form method="POST" action="{{ route('internal.kabalai.permohonan.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-ui.input label="NIB" name="nib" :value="old('nib')" placeholder="13 digit NIB" :error="$errors->first('nib')" required />
                <x-ui.input label="Nama PBF" name="nama_pbf" :value="old('nama_pbf')" placeholder="Nama lengkap usaha" :error="$errors->first('nama_pbf')" required />
            </div>

            <x-ui.textarea label="Alamat" name="alamat" :value="old('alamat')" placeholder="Alamat lengkap PBF" :rows="2" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-ui.input label="Email" name="email" type="email" :value="old('email')" placeholder="email@pbf.id" :error="$errors->first('email')" required />
                <x-ui.input label="No. WhatsApp" name="no_whatsapp" type="text" :value="old('no_whatsapp')" placeholder="08xxxxxxxxxx" :error="$errors->first('no_whatsapp')" required />
            </div>

            {{-- Upload dokumen: 5 input terpisah, bersumber dari DokumenPermohonan::JENIS --}}
            <div>
                <h3 class="text-sm font-semibold text-slate-900 mb-1">Dokumen Pendukung</h3>
                <p class="text-xs text-slate-500 mb-3">Boleh dilengkapi belakangan sebelum evaluasi Staff.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($jenisDokumen as $kode => $meta)
                        <x-ui.file-input
                            :label="$loop->iteration . '. ' . $meta['label']"
                            :name="$kode"
                            :accept="$accept"
                            :help="$meta['keterangan']" />
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <x-ui.button type="submit" variant="default">Simpan & Kirim</x-ui.button>
                <x-ui.button variant="outline" href="{{ route('internal.kabalai.permohonan.index') }}">Batal</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
@endsection
