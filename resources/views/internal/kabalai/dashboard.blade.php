@extends('layouts.internal')

@section('title', 'Dashboard')
@section('content')
<?php $pageTitle = 'Dashboard Kepala Balai'; ?>

{{--
    Dashboard oversight Kepala Balai: memantau SELURUH permohonan balai (view-only,
    tanpa tombol aksi — CLAUDE.md §3 poin 8). Satu-satunya aksi (input permohonan
    baru) ada di halaman Permohonan.
--}}

{{-- KPI Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <x-ui.stat-card :value="$permohonans->count()" label="Total Permohonan" description="Seluruh balai" icon="ph-files" />
    <x-ui.stat-card :value="$permohonans->where('status_saat_ini', 'pengajuan')->count()" label="Perlu Disposisi" description="Menunggu disposisi" icon="ph-paper-plane-tilt" />
    <x-ui.stat-card :value="$permohonans->whereIn('status_saat_ini', ['didisposisikan','proses_evaluasi','revisi_1','revisi_2','revisi_3','menunggu_surat_pengesahan'])->count()" label="Sedang Diproses" description="Dalam pengerjaan" icon="ph-magnifying-glass" />
    <x-ui.stat-card :value="$permohonans->where('status_saat_ini', 'terbit_surat_pengesahan')->count()" label="Terbit Surat" description="Selesai terbit" icon="ph-check-circle" />
</div>

{{-- Status SLA & ringkasan status --}}
<div class="grid gap-4 lg:grid-cols-3 mb-6">
    <x-ui.card class="lg:col-span-1">
        <x-ui.card-content>
            <h3 class="text-sm font-semibold text-blue-900 mb-3">Status SLA</h3>
            <dl class="space-y-2">
                @foreach([
                    ['on_time', 'Tepat waktu', 'bg-emerald-500', 'text-emerald-700'],
                    ['at_risk', 'Mendekati batas', 'bg-amber-400', 'text-amber-700'],
                    ['late', 'Terlambat', 'bg-red-500', 'text-red-700'],
                    ['clock_off', 'Clock-off (menunggu pemohon)', 'bg-amber-300', 'text-amber-700'],
                ] as [$key, $label, $dot, $teks])
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full {{ $dot }}"></span>
                        <dt class="flex-1 text-xs text-slate-600">{{ $label }}</dt>
                        <dd class="text-sm font-semibold {{ $teks }}">{{ $slaRingkasan[$key] }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-3 text-[11px] leading-relaxed text-slate-400">
                Dihitung untuk tahap yang sedang berjalan; tahap revisi tidak dihitung sebagai keterlambatan (clock-off).
            </p>
        </x-ui.card-content>
    </x-ui.card>

    <x-ui.card class="lg:col-span-2">
        <x-ui.card-content>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-blue-900">Rincian Status</h3>
                <x-ui.button variant="outline" size="sm" href="{{ route('internal.kabalai.permohonan.create') }}">
                    <i class="ph ph-plus" aria-hidden="true"></i> Input Permohonan
                </x-ui.button>
            </div>
            @php
                $rincian = [
                    'pengajuan' => 'Pengajuan',
                    'didisposisikan' => 'Didisposisikan',
                    'proses_evaluasi' => 'Proses Evaluasi',
                    'revisi_1' => 'Revisi 1', 'revisi_2' => 'Revisi 2', 'revisi_3' => 'Revisi 3',
                    'menunggu_surat_pengesahan' => 'Menunggu Surat',
                    'terbit_surat_pengesahan' => 'Terbit Surat',
                    'ditutup_pengajuan_ulang' => 'Ditutup',
                ];
                $hitung = $permohonans->countBy('status_saat_ini');
            @endphp
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                @foreach($rincian as $kode => $label)
                    <div class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50/60 px-3 py-2">
                        <x-ui.status-badge :status="$kode" />
                        <span class="text-sm font-semibold text-slate-700">{{ $hitung[$kode] ?? 0 }}</span>
                    </div>
                @endforeach
            </div>
        </x-ui.card-content>
    </x-ui.card>
</div>

{{-- Recent Permohonan Table --}}
<h2 class="text-base font-semibold text-blue-900 mb-3">Permohonan Terbaru</h2>
<x-ui.card>
    <x-ui.card-content class="p-0">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">No. Reg</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">PBF</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">SLA</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tanggal</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                </tr>
            </thead>
            @forelse($permohonans->take(10) as $p)
            <x-ui.permohonan-row :permohonan="$p" :colspan="6">
                <td class="px-4 py-3 font-mono text-xs font-medium text-slate-900">{{ $p->no_registrasi }}</td>
                <td class="px-4 py-3 text-slate-700">{{ $p->nama_pbf_snapshot }}</td>
                <td class="px-4 py-3"><x-ui.status-badge :status="$p->status_saat_ini" /></td>
                <td class="px-4 py-3"><x-ui.sla-badge :sla="app(\App\Services\SlaCalculator::class)->evaluasiPermohonan($p)" /></td>
                <td class="px-4 py-3 text-slate-400 text-xs">{{ $p->tanggal_pengajuan?->format('d M Y') }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <x-ui.button variant="ghost" size="sm" href="{{ route('internal.permohonan.show', $p) }}">Detail</x-ui.button>
                    <x-ui.timeline-toggle />
                </td>
            </x-ui.permohonan-row>
            @empty
            <tbody>
                <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">Belum ada permohonan.</td></tr>
            </tbody>
            @endforelse
        </table>
        </div>
    </x-ui.card-content>
</x-ui.card>
@endsection
