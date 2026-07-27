@extends('layouts.internal')

@section('title', 'Detail Permohonan')
@section('content')
<?php
$pageTitle = 'Detail: ' . $permohonan->no_registrasi;
$user = auth()->user();
$canEdit = $user->isKepalaBalai() && $user->id === $permohonan->kepala_balai_id && !$permohonan->isStatusAkhir();
?>

{{-- Back --}}
<div class="mb-4">
    <a href="{{ url()->previous() }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 transition-colors">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali
    </a>
</div>

{{-- Header --}}
<x-ui.card class="mb-4">
    <x-ui.card-content>
        <div class="flex items-start justify-between mb-4">
            <div>
                <p class="font-mono text-sm font-semibold text-slate-900">{{ $permohonan->no_registrasi }}</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ $permohonan->tanggal_pengajuan?->format('d M Y, H:i') }}</p>
            </div>
            <x-ui.status-badge :status="$permohonan->status_saat_ini" />
        </div>

        @if($permohonan->parent_permohonan_id)
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">
                <p class="text-xs text-amber-700">
                    <strong>Pengajuan Ulang</strong> — dari
                    <span class="font-mono">{{ $permohonan->pengajuanAsal->no_registrasi ?? $permohonan->parent_permohonan_id }}</span>
                </p>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <div>
                <p class="text-xs text-slate-400 uppercase tracking-wide">Nama PBF</p>
                <p class="font-medium text-slate-900">{{ $permohonan->nama_pbf_snapshot }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-400 uppercase tracking-wide">NIB</p>
                <p class="font-mono text-slate-700">{{ $permohonan->nib_snapshot }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-400 uppercase tracking-wide">Email</p>
                <p class="text-slate-700">{{ $permohonan->email_snapshot }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-400 uppercase tracking-wide">No. WhatsApp</p>
                <p class="text-slate-700">{{ $permohonan->no_wa_snapshot }}</p>
            </div>
        </div>
    </x-ui.card-content>
</x-ui.card>

{{-- Timeline --}}
<x-ui.card class="mb-4">
    <x-ui.card-header title="Timeline" description="Riwayat status permohonan" />
    <x-ui.card-content>
        <x-layouts.timeline :logs="$permohonan->statusLog" />
    </x-ui.card-content>
</x-ui.card>

{{-- Dokumen --}}
<x-ui.card class="mb-4">
    <x-ui.card-header title="Dokumen" description="Dokumen yang diupload" />
    <x-ui.card-content class="p-0">
        <ul class="divide-y divide-slate-50">
            @forelse($permohonan->dokumen as $d)
            <li class="px-4 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-800">{{ $d->label }}</p>
                    <p class="text-xs text-slate-400">{{ $d->nama_file_asli }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-400 hidden sm:block">{{ number_format($d->ukuran_file_kb) }} KB</span>
                    <x-ui.button variant="ghost" size="sm" href="{{ route('internal.download.dokumen', [$permohonan, $d->jenis_dokumen]) }}" target="_blank">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </x-ui.button>
                </div>
            </li>
            @empty
            <li class="px-4 py-6 text-sm text-slate-400 text-center">Belum ada dokumen.</li>
            @endforelse
        </ul>
    </x-ui.card-content>
</x-ui.card>

{{-- Surat Pengesahan --}}
@if($permohonan->suratPengesahan)
    <x-ui.card class="mb-4">
        <x-ui.card-header title="Surat Pengesahan" />
        <x-ui.card-content>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-900">{{ $permohonan->suratPengesahan->nomor_surat }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $permohonan->suratPengesahan->tanggal_upload->format('d M Y') }}</p>
                </div>
                <x-ui.button variant="default" size="sm" href="{{ route('internal.download.surat', $permohonan) }}" target="_blank">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Unduh
                </x-ui.button>
            </div>
        </x-ui.card-content>
    </x-ui.card>
@endif

{{-- Actions (Kabalai only, if not final) --}}
@if($canEdit)
    <x-ui.card>
        <x-ui.card-header title="Aksi" description="Ubah data permohonan" />
        <x-ui.card-content>
            <div class="flex gap-3">
                <x-ui.button variant="outline" size="sm" href="{{ route('internal.kabalai.permohonan.edit', $permohonan) }}">Edit Data</x-ui.button>
            </div>
        </x-ui.card-content>
    </x-ui.card>
@endif
@endsection
