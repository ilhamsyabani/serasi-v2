@extends('layouts.internal')

@section('title', 'Edit Permohonan')
@section('content')
<?php $pageTitle = 'Edit: ' . $permohonan->no_registrasi; ?>

<div class="mb-6">
    <a href="{{ route('internal.kabalai.permohonan.show', $permohonan) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 transition-colors">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke Detail
    </a>
</div>

<x-ui.card>
    <x-ui.card-header title="Edit Data Permohonan" description="Ubah informasi pemohon dan data permohonan" />
    <x-ui.card-content>
        <form method="POST" action="{{ route('internal.kabalai.permohonan.update', $permohonan) }}" class="space-y-5 max-w-lg">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-ui.input label="NIB" name="nib_snapshot" :value="old('nib_snapshot', $permohonan->nib_snapshot)" readonly class="!bg-slate-50" />
                <x-ui.input label="Nama PBF" name="nama_pbf_snapshot" :value="old('nama_pbf_snapshot', $permohonan->nama_pbf_snapshot)" required />
            </div>

            <x-ui.input label="Email" name="email_snapshot" type="email" :value="old('email_snapshot', $permohonan->email_snapshot)" required />
            <x-ui.input label="No. WhatsApp" name="no_wa_snapshot" :value="old('no_wa_snapshot', $permohonan->no_wa_snapshot)" required />

            <div class="flex items-center gap-3 pt-2">
                <x-ui.button type="submit" variant="default">Simpan</x-ui.button>
                <x-ui.button variant="outline" href="{{ route('internal.kabalai.permohonan.show', $permohonan) }}">Batal</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
@endsection
