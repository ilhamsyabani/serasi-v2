@extends('layouts.internal')

@section('title', 'Ajukan Ulang Permohonan')
@section('content')
<?php $pageTitle = 'Ajukan Ulang Permohonan'; ?>

@php
    $jenisDokumen = \App\Models\DokumenPermohonan::JENIS;
    $accept = '.' . implode(',.', \App\Models\DokumenPermohonan::EKSTENSI_DIIZINKAN);
    $pbf = $lama->pbf;
@endphp

<div class="mb-6">
    <a href="{{ route('internal.kabalai.pengajuan-ulang.index') }}" class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-slate-800">
        <i class="ph ph-arrow-left" aria-hidden="true"></i> Kembali ke daftar
    </a>
    <h2 class="mt-2 text-lg font-semibold text-slate-900">Ajukan Ulang Permohonan</h2>
    <p class="text-sm text-slate-500">Permohonan baru akan memakai akun PBF yang sama dan tertaut ke <span class="font-mono">{{ $lama->no_registrasi }}</span>.</p>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- Ringkasan permohonan lama & data PBF (hanya-baca) --}}
    <div class="space-y-6 lg:col-span-1">
        <x-ui.card>
            <x-ui.card-header title="Permohonan Lama" />
            <x-ui.card-content class="space-y-3 text-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-mono text-xs font-medium text-slate-900">{{ $lama->no_registrasi }}</span>
                    <x-ui.status-badge :status="$lama->status_saat_ini" />
                </div>
                <p class="text-xs text-slate-500">Ditutup setelah {{ $jumlahRevisi }} kali revisi.</p>
                <div>
                    <p class="text-xs font-semibold text-slate-700">Catatan evaluasi terakhir</p>
                    <p class="mt-1 whitespace-pre-line rounded-lg bg-amber-50 p-3 text-xs text-amber-900">{{ $alasan ?? 'Tidak ada catatan.' }}</p>
                </div>
                <x-ui.button variant="ghost" size="sm" href="{{ route('internal.kabalai.permohonan.show', $lama) }}" target="_blank">
                    <i class="ph ph-arrow-square-out mr-1" aria-hidden="true"></i> Lihat detail & riwayat
                </x-ui.button>
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card>
            <x-ui.card-header title="Data PBF" description="Diambil dari akun yang sudah terdaftar." />
            <x-ui.card-content>
                <dl class="space-y-2 text-sm">
                    @foreach(['NIB' => $pbf->nib, 'Nama PBF' => $pbf->nama_pbf, 'Alamat' => $pbf->alamat ?: '—', 'Email' => $pbf->email, 'No. WhatsApp' => $pbf->no_whatsapp] as $label => $nilai)
                        <div>
                            <dt class="text-xs text-slate-500">{{ $label }}</dt>
                            <dd class="font-medium text-slate-800 break-words">{{ $nilai }}</dd>
                        </div>
                    @endforeach
                </dl>
                <p class="mt-4 text-xs text-slate-500">Pemohon login dengan akun yang sama dan akan menerima notifikasi WhatsApp & email. Perubahan kontak PBF dilakukan terpisah, bukan dari form ini.</p>
            </x-ui.card-content>
        </x-ui.card>
    </div>

    {{-- Unggah dokumen --}}
    <div class="lg:col-span-2">
        <x-ui.card>
            <x-ui.card-header title="Dokumen Perbaikan" description="Unggah minimal satu dokumen. Dokumen lain boleh dilengkapi sebelum evaluasi Staff." />
            <x-ui.card-content>
                <form method="POST" action="{{ route('internal.kabalai.pengajuan-ulang.store', $lama) }}" enctype="multipart/form-data" class="space-y-5"
                      x-data="{ kirim: false }" x-on:submit="kirim = true">
                    @csrf

                    @error('dokumen')
                        <x-ui.alert type="error">{{ $message }}</x-ui.alert>
                    @enderror

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach($jenisDokumen as $kode => $meta)
                            <x-ui.file-input
                                :label="$loop->iteration . '. ' . $meta['label']"
                                :name="$kode"
                                :accept="$accept"
                                :help="$meta['keterangan']" />
                        @endforeach
                    </div>

                    <div class="flex items-center gap-3 border-t border-slate-100 pt-4">
                        <x-ui.button type="submit" variant="default" x-bind:disabled="kirim">
                            <i class="ph ph-arrows-clockwise mr-1.5" aria-hidden="true"></i>
                            <span x-text="kirim ? 'Menyimpan…' : 'Ajukan Ulang'">Ajukan Ulang</span>
                        </x-ui.button>
                        <x-ui.button variant="outline" href="{{ route('internal.kabalai.pengajuan-ulang.index') }}">Batal</x-ui.button>
                    </div>
                </form>
            </x-ui.card-content>
        </x-ui.card>
    </div>
</div>
@endsection
