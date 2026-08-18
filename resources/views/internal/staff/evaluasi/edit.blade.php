@extends('layouts.internal')

@section('title', 'Evaluasi')
@section('content')
<?php
$pageTitle = 'Evaluasi: ' . $permohonan->no_registrasi;
$statusRevisi = in_array($permohonan->status_saat_ini, [
    App\Models\Permohonan::STATUS_REVISI_1,
    App\Models\Permohonan::STATUS_REVISI_2,
    App\Models\Permohonan::STATUS_REVISI_3,
]);
$revisiKe = (int) filter_var($permohonan->status_saat_ini, FILTER_SANITIZE_NUMBER_INT);
$revisiBerikutnya = $revisiKe + 1;
$labelRevisiSekarang = $revisiKe > 0 ? "Revisi {$revisiKe}" : "Proses Awal";
$labelRevisiBerikutnya = "Revisi {$revisiBerikutnya}";
// Banner hanya muncul saat di siklus revisi (artinya revisi sebelumnya sudah diupload pemohon)
$showRevisiBanner = $statusRevisi;
?>

<div x-data="{
    showConfirm: false,
    hasilDipilih: null,
    confirmRevisi() {
        this.hasilDipilih = $event.target.closest('form').querySelector('input[name=\'hasil\']:checked').value;
        if (this.hasilDipilih === 'tidak_lengkap' && {{ $statusRevisi ? 'true' : 'false' }}) {
            this.showConfirm = true;
            // Salin nilai catatan ke hidden field di form konfirmasi
            const mainForm = $event.target.closest('form');
            const catatanValue = mainForm.querySelector('textarea[name=\'catatan\']').value;
            document.getElementById('confirm_catatan').value = catatanValue;
            $event.preventDefault();
        }
    }
}">

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
        @if($showRevisiBanner)
        <div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
            <p class="text-sm text-amber-800">
                <i class="ph ph-warning mr-1" aria-hidden="true"></i>
                Pemohon telah mengunggah revisi untuk <strong>{{ $labelRevisiSekarang }}.
                </strong> Jika Anda memilih <strong>Tidak Lengkap</strong>, siklus revisi akan naik ke <strong>{{ $labelRevisiBerikutnya }}</strong>.
                Kuota maks. 3 revisi.
            </p>
        </div>
        @endif
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
                    <x-ui.button variant="ghost" size="sm" href="{{ route('internal.download.revisi', $dr->revisi) }}" target="_blank">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </x-ui.button>
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
        <form method="POST" action="{{ route('internal.staff.evaluasi.update', $permohonan) }}" class="space-y-5" @submit="confirmRevisi()">
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

{{-- Konfirmasi Naik Siklus Revisi --}}
<div x-show="showConfirm" x-cloak
    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
    @click.self="showConfirm = false" @keydown.escape.window="showConfirm = false">

    <div x-show="showConfirm"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 overflow-hidden">

        <div class="bg-red-500 px-6 py-4">
            <h3 class="text-white font-semibold text-base flex items-center gap-2">
                <i class="ph ph-warning-circle text-xl" aria-hidden="true"></i>
                Konfirmasi Siklus Revisi
            </h3>
        </div>
        <div class="p-6">
            <p class="text-sm text-slate-700 mb-1">
                Revisi telah diunggah oleh pemohon untuk <strong>{{ $labelRevisiSekarang }}</strong>.
            </p>
            <p class="text-sm text-slate-700 mb-4">
                Jika Anda memilih <strong>Tidak Lengkap</strong>, siklus revisi akan naik ke
                <strong class="text-red-600">{{ $labelRevisiBerikutnya }}</strong>.
            </p>
            <div class="p-3 bg-red-50 border border-red-200 rounded-lg mb-4">
                <p class="text-xs text-red-700">
                    <strong>Perhatian:</strong> Kuota maks. 3 siklus revisi. Setelah Revisi 3 tetap tidak lengkap, permohonan akan <strong>ditutup</strong> dan pemohon harus mengajukan ulang.
                </p>
            </div>
            <div class="flex gap-3">
                <button type="button" @click="showConfirm = false" class="flex-1 px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 font-medium text-sm transition-colors">
                    Batal
                </button>
                <form method="POST" action="{{ route('internal.staff.evaluasi.update', $permohonan) }}" class="flex-1" @submit.stop>
                    @csrf @method('PUT')
                    <input type="hidden" name="hasil" value="tidak_lengkap">
                    <input type="hidden" name="catatan" id="confirm_catatan">
                    <x-ui.button type="submit" variant="destructive" class="w-full">Ya, Kirim</x-ui.button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Spinner overlay --}}
<div x-data="{ submitting: false }" @submit="submitting = true" x-show="submitting" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/30">
    <div class="bg-white rounded-xl shadow-xl p-6 flex items-center gap-3">
        <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
        <p class="text-sm font-medium text-slate-700">Menyimpan evaluasi...</p>
    </div>
</div>

</div>
@endsection
