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

@php
    $jenisDokumen = \App\Models\DokumenPermohonan::JENIS;
    $accept = '.' . implode(',.', \App\Models\DokumenPermohonan::EKSTENSI_DIIZINKAN);
    // Index existing documents by jenis_dokumen for quick lookup
    $existingDocs = $permohonan->dokumen->keyBy('jenis_dokumen');
@endphp

<x-ui.card>
    <x-ui.card-header title="Edit Data Permohonan" description="Ubah informasi pemohon" />
    <x-ui.card-content>
        <form method="POST" action="{{ route('internal.kabalai.permohonan.update', $permohonan) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-ui.input label="NIB" name="nib_snapshot" :value="old('nib_snapshot', $permohonan->nib_snapshot)" readonly class="!bg-slate-50" />
                <x-ui.input label="Nama PBF" name="nama_pbf_snapshot" :value="old('nama_pbf_snapshot', $permohonan->nama_pbf_snapshot)" required />
            </div>

            <x-ui.textarea
                label="Alamat"
                name="alamat"
                :value="old('alamat', $permohonan->pbf?->alamat ?? '')"
                placeholder="Alamat lengkap PBF"
                :rows="2" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-ui.input label="Email" name="email_snapshot" type="email" :value="old('email_snapshot', $permohonan->email_snapshot)" required />
                <x-ui.input label="No. WhatsApp" name="no_wa_snapshot" :value="old('no_wa_snapshot', $permohonan->no_wa_snapshot)" required />
            </div>

            {{-- Dokumen: tampilkan yang sudah ada + opsi upload ulang --}}
            <div>
                <h3 class="text-sm font-semibold text-slate-900 mb-1">Dokumen Pendukung</h3>
                <p class="text-xs text-slate-500 mb-3">Upload ulang dokumen untuk mengganti file yang sudah ada.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($jenisDokumen as $kode => $meta)
                        @php $existing = $existingDocs[$kode] ?? null; @endphp
                        <div class="border border-slate-200 rounded-lg p-3 bg-white">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-medium text-slate-800">{{ $loop->iteration }}. {{ $meta['label'] }}</span>
                                @if($existing)
                                    <span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                                        <i class="ph-fill ph-check-circle"></i> Terunggah
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs text-slate-400 font-medium">
                                        <i class="ph ph-clock"></i> Belum ada
                                    </span>
                                @endif
                            </div>

                            @if($existing)
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="flex items-center gap-1.5 text-xs text-slate-500 bg-slate-50 rounded px-2 py-1 flex-1 min-w-0">
                                        <i class="ph ph-file-pdf text-slate-400 shrink-0"></i>
                                        <span class="truncate" title="{{ $existing->nama_file_asli }}">{{ $existing->nama_file_asli }}</span>
                                        <span class="shrink-0 text-slate-400">{{ number_format($existing->ukuran_file_kb) }} KB</span>
                                    </div>
                                    <a href="{{ route('internal.download.dokumen', [$permohonan, $kode]) }}"
                                       target="_blank"
                                       class="inline-flex items-center justify-center w-7 h-7 rounded border border-slate-200 bg-white text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-colors shrink-0"
                                       title="Unduh dokumen">
                                        <i class="ph ph-download-simple text-sm"></i>
                                    </a>
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-500 mb-1 cursor-pointer hover:text-slate-700 transition-colors">
                                        <span class="underline underline-offset-2">Ganti dokumen...</span>
                                        <input type="file" name="{{ $kode }}" accept="{{ $accept }}" class="hidden"
                                            onchange="const l=this.closest('label').querySelector('.file-name');l&&(l.textContent=this.files[0]?.name||'Ganti dokumen...')" />
                                    </label>
                                    <span class="file-name text-xs text-amber-600"></span>
                                </div>
                            @else
                                <input type="file" name="{{ $kode }}" accept="{{ $accept }}" class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer" />
                                <p class="text-[10px] text-slate-400 mt-1">{{ $meta['keterangan'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <x-ui.button type="submit" variant="default">Simpan</x-ui.button>
                <x-ui.button variant="outline" href="{{ route('internal.kabalai.permohonan.show', $permohonan) }}">Batal</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
@endsection
