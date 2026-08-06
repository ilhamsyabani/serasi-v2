@extends('layouts.internal')

@section('title', 'Evaluasi')
@section('content')
<?php $pageTitle = 'Evaluasi: ' . $permohonan->no_registrasi; ?>

<x-ui.card class="mb-6">
    <x-ui.card-header :title="$permohonan->no_registrasi" description="Form evaluasi permohonan" />
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
    <x-ui.card-header title="Dokumen Permohonan" description="Periksa kelengkapan dokumen yang diupload pemohon" />
    <x-ui.card-content class="p-0">
        <ul class="divide-y divide-slate-50">
            @forelse($dokumen as $d)
            <li class="px-6 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-800">{{ Str::title(str_replace('_', ' ', $d->jenis_dokumen)) }}</p>
                    <p class="text-xs text-slate-400">{{ $d->nama_file_asli }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-400">{{ number_format($d->ukuran_file_kb) }} KB</span>
                    <x-ui.button variant="ghost" size="sm" href="{{ route('internal.download.dokumen', [$permohonan, $d->jenis_dokumen]) }}" target="_blank">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </x-ui.button>
                </div>
            </li>
            @empty
            <li class="px-6 py-4 text-sm text-slate-400 text-center">Tidak ada dokumen.</li>
            @endforelse
        </ul>
    </x-ui.card-content>
</x-ui.card>

@if($dokumenRevisi->isNotEmpty())
<x-ui.card class="mt-4">
    <x-ui.card-header title="Dokumen Revisi" description="Dokumen yang diupload pemohon sebagai hasil revisi" />
    <x-ui.card-content class="p-0">
        <ul class="divide-y divide-slate-50">
            @foreach($dokumenRevisi as $dr)
            <li class="px-6 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-800">{{ $dr->nama_file_asli }}</p>
                    <p class="text-xs text-slate-400">{{ number_format($dr->ukuran_file_kb, 2) }} KB &middot; {{ $dr->uploaded_at?->format('d M Y H:i') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ Storage::url($dr->path_file) }}" target="_blank" class="inline-flex items-center justify-center w-8 h-8 rounded border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </a>
                </div>
            </li>
            @endforeach
        </ul>
    </x-ui.card-content>
</x-ui.card>
@endif

<x-ui.card class="mt-4">
    <x-ui.card-header title="Form Evaluasi" description="Tentukan kelengkapan permohonan" />
    <x-ui.card-content>
        <form method="POST" action="{{ route('internal.staff.evaluasi.update', $permohonan) }}" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <p class="text-sm font-medium text-slate-700 mb-2">Hasil Evaluasi</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-4 border border-slate-200 rounded-lg cursor-pointer hover:bg-emerald-50 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 has-[:checked]:ring-1 has-[:checked]:ring-emerald-500 transition-colors">
                        <input type="radio" name="hasil" value="lengkap" class="text-emerald-600 focus:ring-emerald-500" {{ old('hasil')=='lengkap'?'checked':'' }}>
                        <div>
                            <p class="font-medium text-slate-900">Lengkap</p>
                            <p class="text-xs text-slate-500">Semua dokumen sesuai</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 p-4 border border-slate-200 rounded-lg cursor-pointer hover:bg-red-50 has-[:checked]:border-red-500 has-[:checked]:bg-red-50 has-[:checked]:ring-1 has-[:checked]:ring-red-500 transition-colors">
                        <input type="radio" name="hasil" value="tidak_lengkap" class="text-red-600 focus:ring-red-500" {{ old('hasil')=='tidak_lengkap'?'checked':'' }}>
                        <div>
                            <p class="font-medium text-slate-900">Tidak Lengkap</p>
                            <p class="text-xs text-slate-500">Perlu revisi</p>
                        </div>
                    </label>
                </div>
            </div>

            <x-ui.textarea label="Catatan Ketidaksesuaian" name="catatan" :value="old('catatan')" placeholder="Jelaskan dokumen atau informasi yang perlu diperbaiki oleh pemohon..." :rows="4" />

            <div class="flex items-center gap-3 pt-2">
                <x-ui.button type="submit" variant="default">Simpan Evaluasi</x-ui.button>
                <x-ui.button variant="outline" href="{{ route('internal.staff.dashboard') }}">Batal</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
@endsection
