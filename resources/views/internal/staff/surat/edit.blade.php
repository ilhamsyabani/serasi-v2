@extends('layouts.internal')

@section('title', 'Upload Surat Pengesahan')
@section('content')
<?php $pageTitle = 'Upload Surat: ' . $permohonan->no_registrasi; ?>

<x-ui.card class="mb-6">
    <x-ui.card-header :title="$permohonan->no_registrasi" description="Permohonan telah memenuhi persyaratan" />
    <x-ui.card-content>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-xs text-slate-400 uppercase tracking-wide">PBF</p>
                <p class="font-medium text-slate-900">{{ $permohonan->nama_pbf_snapshot }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-400 uppercase tracking-wide">NIB</p>
                <p class="font-mono text-slate-700">{{ $permohonan->nib_snapshot }}</p>
            </div>
        </div>
    </x-ui.card-content>
</x-ui.card>

<x-ui.card>
    <x-ui.card-header title="Upload Surat Pengesahan" description="Scan/unggah surat pengesahan yang sudah ditandatangani basah" />
    <x-ui.card-content>
        <form method="POST" action="{{ route('internal.staff.surat.update', $permohonan) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf @method('PUT')

            <x-ui.file-input
                label="File Surat (PDF)"
                name="file_surat"
                accept=".pdf"
                :help="'Format: PDF. Maks. 10MB.'"
                :required="true"
            />

            <x-ui.input label="Nomor Surat" name="nomor_surat" :value="old('nomor_surat')" placeholder="Contoh: 123/PBF/DENAH/BBPOM/2026" required />

            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                <p class="text-sm text-emerald-800">
                    <strong>Catatan:</strong> Pastikan surat sudah di TTE dari Srikandi oleh pejabat berwenang sebelum diupload. Surat yang sudah diupload tidak dapat diubah.
                </p>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <x-ui.button type="submit" variant="default">Terbitkan Surat</x-ui.button>
                <x-ui.button variant="outline" href="{{ route('internal.staff.dashboard') }}">Batal</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
@endsection
