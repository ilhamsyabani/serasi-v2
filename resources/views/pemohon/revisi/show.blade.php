@extends('layouts.pemohon')

@section('title', 'Upload Revisi')

@section('content')
{{-- Back Button --}}
<div class="mb-4">
    <a href="{{ route('pemohon.permohonan.show', $permohonan) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 transition-colors">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali
    </a>
</div>

{{-- Header --}}
<x-ui.card class="mb-4">
    <x-ui.card-content>
        <div class="flex items-start justify-between">
            <div>
                <p class="font-mono text-sm font-semibold text-slate-900">{{ $permohonan->no_registrasi }}</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ $permohonan->nama_pbf_snapshot }}</p>
            </div>
            <x-ui.status-badge :status="$permohonan->status_saat_ini" />
        </div>
    </x-ui.card-content>
</x-ui.card>

{{-- Catatan Revisi --}}
@if($evaluasiTerakhir)
    <x-ui.card class="mb-4 border-l-4 border-l-amber-500">
        <x-ui.card-header title="Catatan dari Staff Sertifikasi" description="Perbaiki hal-hal berikut" />
        <x-ui.card-content>
            <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $evaluasiTerakhir->catatan ?: 'Tidak ada catatan spesifik. Mohon perbaiki dokumen sesuai standar.' }}</p>
        </x-ui.card-content>
    </x-ui.card>
@endif

{{-- Upload Form --}}
<x-ui.card>
    <x-ui.card-header title="Upload Dokumen Revisi" description="Unggah dokumen yang telah diperbaiki sesuai catatan" />
    <x-ui.card-content>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-5">
            <p class="text-sm text-blue-800">
                <strong>Panduan Upload:</strong>
            </p>
            <ul class="text-xs text-blue-700 mt-2 space-y-1 list-disc list-inside">
                <li>Upload dokumen yang diminta untuk diperbaiki</li>
                <li>Format: PDF, JPG, PNG (maks. 10MB per file)</li>
                <li>Setelah upload, permohonan akan ditinjau ulang oleh Staff</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('pemohon.revisi.store', $permohonan) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <x-ui.file-input
                label="Pilih Dokumen Revisi"
                name="dokumen"
                multiple
                accept=".pdf,.jpg,.jpeg,.png"
                :help="'Format: PDF, JPG, PNG. Maks. 10MB per file.'"
                :required="true"
            />

            <x-ui.button type="submit" variant="default" class="w-full sm:w-auto">
                📤 Kirim Revisi
            </x-ui.button>
        </form>
    </x-ui.card-content>
</x-ui.card>
@endsection
