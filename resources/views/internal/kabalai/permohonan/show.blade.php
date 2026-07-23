@extends('layouts.internal')

@section('title', 'Detail Permohonan')

@section('content')
    @php
        $pageTitle = 'Detail: ' . $permohonan->no_registrasi;
    @endphp

    {{-- Header & Back Button --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <a href="{{ route('internal.kabalai.permohonan.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 transition-colors font-medium">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali ke Daftar
        </a>

        {{-- Jika ada aksi tambahan di pojok kanan atas, bisa diletakkan di sini --}}
        @unless($permohonan->isStatusAkhir())
            <x-ui.button variant="default" size="md" href="{{ route('internal.kabalai.permohonan.edit', $permohonan) }}">
                <i class="ph ph-pencil-simple mr-1.5"></i> Edit Data
            </x-ui.button>
        @endunless
    </div>

    {{-- Grid Layout Utama: 2 Kolom Kiri, 1 Kolom Kanan pada Desktop --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- KOLOM KIRI (Konten Utama) --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- Informasi Utama --}}
            <x-ui.card>
                <x-ui.card-content>
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-5 border-b border-slate-100 pb-4">
                        <div>
                            <p class="font-mono text-base font-bold text-slate-900">{{ $permohonan->no_registrasi }}</p>
                            <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                                <i class="ph ph-calendar-blank"></i> 
                                {{ $permohonan->tanggal_pengajuan?->format('d M Y, H:i') }}
                            </p>
                        </div>
                        <x-ui.status-badge :status="$permohonan->status_saat_ini" />
                    </div>

                    @if($permohonan->parent_permohonan_id)
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-5 flex items-start gap-2.5">
                            <i class="ph-fill ph-warning-circle text-amber-500 text-lg mt-0.5"></i>
                            <p class="text-xs text-amber-800 leading-relaxed">
                                <strong class="font-semibold">Pengajuan Ulang</strong><br>
                                Permohonan ini merupakan pengajuan ulang dari nomor registrasi 
                                <span class="font-mono font-medium">{{ $permohonan->pengajuanAsal->no_registrasi ?? $permohonan->parent_permohonan_id }}</span>
                            </p>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-5 gap-x-4 text-sm">
                        <div>
                            <p class="text-[11px] text-slate-400 uppercase tracking-wider font-semibold mb-1">Nama PBF</p>
                            <p class="font-medium text-slate-900">{{ $permohonan->nama_pbf_snapshot }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 uppercase tracking-wider font-semibold mb-1">NIB</p>
                            <p class="font-mono text-slate-800">{{ $permohonan->nib_snapshot }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 uppercase tracking-wider font-semibold mb-1">Email</p>
                            <p class="text-slate-800">{{ $permohonan->email_snapshot }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 uppercase tracking-wider font-semibold mb-1">No. WhatsApp</p>
                            <p class="text-slate-800">{{ $permohonan->no_wa_snapshot }}</p>
                        </div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            {{-- Dokumen --}}
            <x-ui.card>
                <x-ui.card-header title="Dokumen Lampiran" description="File yang diunggah oleh pemohon" />
                <x-ui.card-content class="p-0">
                    <ul class="divide-y divide-slate-100">
                        @forelse($permohonan->dokumen as $d)
                            <li class="px-5 py-3.5 flex items-center justify-between hover:bg-slate-50 transition-colors">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 text-slate-400">
                                        <i class="ph-fill ph-file-pdf text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-800">{{ $d->label }}</p>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $d->nama_file_asli }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-slate-400 hidden sm:block bg-slate-100 px-2 py-1 rounded">{{ number_format($d->ukuran_file_kb) }} KB</span>
                                    <x-ui.button variant="ghost" size="sm" href="{{ asset('storage/' . $d->path_file) }}" target="_blank" title="Unduh Dokumen">
                                        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                    </x-ui.button>
                                </div>
                            </li>
                        @empty
                            <li class="px-5 py-8 flex flex-col items-center justify-center text-slate-400">
                                <i class="ph ph-folder-open text-3xl mb-2 text-slate-300"></i>
                                <p class="text-sm">Belum ada dokumen yang diunggah.</p>
                            </li>
                        @endforelse
                    </ul>
                </x-ui.card-content>
            </x-ui.card>

            {{-- Surat Pengesahan (Hanya tampil jika ada) --}}
            @if($permohonan->suratPengesahan)
                <x-ui.card class="border-l-4 border-l-emerald-500 overflow-hidden shadow-sm">
                    <div class="bg-emerald-50/50 px-5 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <div class="bg-emerald-100 p-2 rounded-full text-emerald-600">
                                <i class="ph-fill ph-seal-check text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 mb-0.5">Surat Pengesahan Diterbitkan</h3>
                                <p class="text-xs text-slate-600">
                                    No: <span class="font-medium text-slate-900">{{ $permohonan->suratPengesahan->nomor_surat }}</span> 
                                    &bull; {{ $permohonan->suratPengesahan->tanggal_upload->format('d M Y') }}
                                </p>
                            </div>
                        </div>
                        <x-ui.button variant="default" size="sm" href="{{ asset('storage/' . $permohonan->suratPengesahan->path_file) }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white shrink-0">
                            <i class="ph ph-download-simple mr-1.5"></i> Unduh Surat
                        </x-ui.button>
                    </div>
                </x-ui.card>
            @endif

        </div>

        {{-- KOLOM KANAN (Sidebar: Timeline) --}}
        <div class="lg:col-span-1 space-y-6">
            <x-ui.card class="sticky top-6">
                <x-ui.card-header title="Riwayat Status" description="Timeline proses permohonan" />
                <x-ui.card-content class="pt-2">
                    <x-layouts.timeline :logs="$permohonan->statusLog" />
                </x-ui.card-content>
            </x-ui.card>
        </div>

    </div>
@endsection