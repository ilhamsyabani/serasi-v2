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

@if(session('warning'))
    <x-ui.alert type="warning" class="mb-5">{{ session('warning') }}</x-ui.alert>
@endif

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

            <div x-data="{ waWarning: '', checking: false }">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <x-ui.input label="Email PIC" name="email" type="email" :value="old('email')" placeholder="email@pbf.id" :error="$errors->first('email')" required />
                    <div>
                        <x-ui.input label="No. WhatsApp PIC" name="no_whatsapp" type="text" :value="old('no_whatsapp')" placeholder="08xxxxxxxxxx" :error="$errors->first('no_whatsapp')" required
                            x-on:blur="checking = true; fetch('/api/check-whatsapp?no=' + $event.target.value).then(r => r.json()).then(d => { waWarning = d.warning || ''; checking = false; }).catch(() => { checking = false; })"
                            x-bind:class="waWarning ? 'ring-2 ring-amber-400' : ''" />
                        <p x-show="checking" class="text-xs text-slate-400 mt-1">Memeriksa...</p>
                        <template x-if="waWarning && !checking">
                            <p class="text-xs text-amber-600 mt-1 flex items-center gap-1">
                                <span>⚠️</span><span x-text="waWarning"></span>
                            </p>
                        </template>
                    </div>
                </div>
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
