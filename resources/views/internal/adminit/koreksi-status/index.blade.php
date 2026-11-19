@extends('layouts.internal')

@section('title', 'Koreksi Status')
@section('content')
<?php $pageTitle = 'Koreksi Status'; ?>

<div x-data="koreksiStatus()">

<div class="flex items-start justify-between mb-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Kembalikan Permohonan ke Revisi</h2>
        <p class="text-sm text-slate-500">
            Untuk permohonan yang keliru dinilai Lengkap. Permohonan dinilai ulang Tidak Lengkap dan masuk ke
            <strong>revisi berikutnya</strong>. Kalau kuota {{ $maksRevisi }} revisi sudah habis, permohonan otomatis
            <strong>ditutup</strong>. Riwayat lama tetap tersimpan.
        </p>
    </div>
</div>

@if($errors->any())
    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
        @foreach($errors->all() as $e) <p>{{ $e }}</p> @endforeach
    </div>
@endif

<form method="GET" action="{{ route('internal.adminit.koreksi-status.index') }}" class="mb-4">
    <div class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[200px]">
            <x-ui.input label="Cari" name="search" :value="request('search')" placeholder="No. registrasi atau nama PBF..." />
        </div>
        <div class="flex gap-2 items-end">
            <x-ui.button variant="default" type="submit">Cari</x-ui.button>
            <x-ui.button variant="outline" href="{{ route('internal.adminit.koreksi-status.index') }}">Reset</x-ui.button>
        </div>
    </div>
</form>

<x-ui.card>
    <x-ui.card-content class="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Permohonan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status Sekarang</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Evaluasi Terakhir</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Jika Dikembalikan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($permohonan as $p)
                    @php $ditutup = $p->koreksi_tujuan === \App\Models\Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG; @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <a href="{{ route('internal.permohonan.show', $p) }}" class="font-medium text-slate-800 hover:text-emerald-600">{{ $p->no_registrasi }}</a>
                            <p class="text-xs text-slate-500">{{ $p->nama_pbf_snapshot }}</p>
                            <p class="text-xs text-slate-400">Evaluator: {{ $p->distribusiAktif?->staff?->nama ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.status-badge :status="$p->status_saat_ini" />
                            <p class="text-xs text-slate-400 mt-1">Sudah {{ (int) $p->revisi_ke }} / {{ $maksRevisi }} revisi</p>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600">
                            @if($p->evaluasiTerakhir)
                                <span class="font-medium {{ $p->evaluasiTerakhir->hasil === 'lengkap' ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $p->evaluasiTerakhir->hasil === 'lengkap' ? 'Lengkap' : 'Tidak Lengkap' }}
                                </span>
                                <span class="text-slate-400">· {{ $p->evaluasiTerakhir->tanggal_evaluasi?->format('d M Y') }}</span>
                            @else
                                <span class="text-slate-400">Belum ada</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.status-badge :status="$p->koreksi_tujuan" />
                            @if(!$ditutup && $p->koreksi_tujuan === 'revisi_' . $maksRevisi)
                                <p class="text-xs text-amber-600 mt-1">Revisi terakhir</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($p->koreksi_tolak)
                                <span class="text-xs text-slate-400" title="{{ $p->koreksi_tolak }}">Tidak dapat dikoreksi</span>
                            @else
                                <button type="button"
                                    class="inline-flex items-center justify-center h-8 px-3 text-xs gap-1.5 rounded-lg font-medium border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors"
                                    x-on:click="buka({{ Js::from([
                                        'url' => route('internal.adminit.koreksi-status.store', $p),
                                        'no' => $p->no_registrasi,
                                        'pbf' => $p->nama_pbf_snapshot,
                                        'tujuan' => $ditutup ? 'DITUTUP – perlu pengajuan ulang' : 'Revisi ke-' . substr($p->koreksi_tujuan, 7),
                                        'ditutup' => $ditutup,
                                        'terakhir' => !$ditutup && $p->koreksi_tujuan === 'revisi_' . $maksRevisi,
                                    ]) }})">
                                    <i class="ph ph-arrow-counter-clockwise" aria-hidden="true"></i> Kembalikan
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5">
                        <x-ui.empty-state title="Tidak ada permohonan yang dapat dikoreksi"
                            description="Hanya permohonan berstatus Menunggu Surat atau Proses Evaluasi yang dapat dikembalikan ke revisi." />
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card-content>
</x-ui.card>

<div class="mt-4">{{ $permohonan->links() }}</div>

{{-- Modal konfirmasi --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
     @click.self="open = false" @keydown.escape.window="open = false">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full overflow-hidden">
        <div class="px-6 py-4" :class="row.ditutup ? 'bg-red-600' : 'bg-amber-500'">
            <h3 class="text-white font-semibold text-base flex items-center gap-2">
                <i class="ph ph-arrow-counter-clockwise text-xl" aria-hidden="true"></i>
                Kembalikan ke Revisi
            </h3>
        </div>
        <form method="POST" :action="row.url" class="p-6 space-y-4" @submit="if (submitting) { $event.preventDefault(); return; } submitting = true">
            @csrf
            <div class="text-sm text-slate-700">
                <p><strong x-text="row.no"></strong> · <span x-text="row.pbf"></span></p>
                <p class="mt-1">Status akan menjadi <strong :class="row.ditutup ? 'text-red-600' : 'text-amber-600'" x-text="row.tujuan"></strong>.</p>
            </div>
            <div x-show="row.ditutup" class="p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700">
                Kuota {{ $maksRevisi }} revisi sudah habis. Permohonan akan <strong>ditutup</strong> dan pemohon harus mengajukan ulang.
            </div>
            <div x-show="row.terakhir" class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700">
                Ini <strong>revisi terakhir</strong>. Kalau hasil berikutnya masih Tidak Lengkap, permohonan akan ditutup.
            </div>

            <x-ui.textarea label="Catatan ketidaksesuaian (dibaca pemohon)" name="catatan" :rows="4" required
                placeholder="Jelaskan apa yang perlu diperbaiki oleh pemohon..." />
            <x-ui.textarea label="Alasan koreksi (internal, untuk audit trail)" name="alasan" :rows="2" required
                placeholder="Mis. evaluator keliru memilih Lengkap" />

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="kirim_notifikasi" value="1" checked class="rounded text-emerald-600 focus:ring-emerald-500">
                Kirim notifikasi WA & email ke pemohon, Ketua Tim, dan evaluator
            </label>

            <div class="flex gap-3 pt-2">
                <button type="button" @click="open = false"
                    class="flex-1 h-10 px-4 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 font-medium text-sm transition-colors">Batal</button>
                <button type="submit" :disabled="submitting"
                    class="flex-1 h-10 px-4 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium text-sm transition-colors disabled:bg-slate-200 disabled:text-slate-400">Ya, Kembalikan</button>
            </div>
        </form>
    </div>
</div>

</div>

<script>
function koreksiStatus() {
    return {
        open: false,
        submitting: false,
        row: { url: '', no: '', pbf: '', tujuan: '', ditutup: false, terakhir: false },
        buka(row) { this.row = row; this.submitting = false; this.open = true; },
    };
}
</script>
@endsection
