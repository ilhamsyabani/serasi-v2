@extends('layouts.pemohon')

@section('title', 'Ajukan Ulang')

@section('content')
<div class="mb-4">
    <a href="{{ route('pemohon.permohonan.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 transition-colors">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali
    </a>
</div>

<x-ui.card class="mb-4">
    <x-ui.card-content>
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <p class="text-sm text-amber-800">
                <strong>Pengajuan Ulang</strong><br>
                Anda mengajukan ulang dari permohonan
                <span class="font-mono font-semibold">{{ $parent->no_registrasi }}</span>.
            </p>
        </div>
    </x-ui.card-content>
</x-ui.card>

<x-ui.card>
    <x-ui.card-header title="Upload Dokumen Baru" description="Upload ulang 5 dokumen persyaratan" />
    <x-ui.card-content>
        <form method="POST" action="{{ route('pemohon.permohonan.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                <p class="text-sm font-medium text-slate-700 mb-2">Dokumen yang Diperlukan:</p>
                <ul class="text-xs text-slate-600 space-y-1 list-disc list-inside">
                    <li>Surat Permohonan bermaterai</li>
                    <li>Surat Pernyataan</li>
                    <li>Rancangan Denah PBF</li>
                    <li>Izin PBF (NIE)</li>
                    <li>STRA / SIK Penanggung Jawab</li>
                </ul>
            </div>

            <x-ui.file-input
                label="Upload Dokumen (maks. 5 file)"
                name="dokumen"
                multiple
                accept=".pdf,.jpg,.jpeg,.png"
                :help="'Format: PDF, JPG, PNG. Maks. 10MB per file.'"
                :required="true"
            />

            <x-ui.button type="submit" variant="default" class="w-full sm:w-auto">
                📤 Kirim Pengajuan Ulang
            </x-ui.button>
        </form>
    </x-ui.card-content>
</x-ui.card>
@endsection
