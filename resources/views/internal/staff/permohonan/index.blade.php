@extends('layouts.internal')

@section('title', 'Permohonan')
@section('content')
<?php $pageTitle = 'Permohonan'; ?>

@php
$tabs = [
    'belum_evaluasi' => ['label' => 'Belum Dievaluasi', 'icon' => 'ph-clock', 'color' => 'amber'],
    'sudah_evaluasi' => ['label' => 'Sudah Dievaluasi', 'icon' => 'ph-check-circle', 'color' => 'blue'],
    'terbit' => ['label' => 'Terbit', 'icon' => 'ph-seal-check-fill', 'color' => 'emerald'],
];

$filterLabel = $tabs[$filter]['label'] ?? 'Permohonan';
$filterColor = $tabs[$filter]['color'] ?? 'slate';
@endphp

{{-- Tab Filter --}}
<div class="mb-4">
    <div class="flex flex-wrap items-end gap-4">
        {{-- Tab Buttons --}}
        <div class="flex items-center gap-1.5 bg-slate-100 rounded-xl p-1">
            @foreach($tabs as $key => $tab)
                @php
                    $isActive = $filter === $key;
                    $colorMap = [
                        'amber' => ['active' => 'bg-amber-500 text-white', 'badge' => 'bg-amber-100 text-amber-700'],
                        'blue' => ['active' => 'bg-blue-500 text-white', 'badge' => 'bg-blue-100 text-blue-700'],
                        'emerald' => ['active' => 'bg-emerald-500 text-white', 'badge' => 'bg-emerald-100 text-emerald-700'],
                    ];
                    $colors = $colorMap[$tab['color']];
                @endphp
                <a href="{{ route('internal.staff.permohonan.index', ['filter' => $key]) }}"
                   @class([
                       'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                       $colors['active'] . ' shadow-sm' => $isActive,
                       'text-slate-500 hover:bg-white hover:text-slate-700' => ! $isActive,
                   ])>
                    <i class="{{ $isActive ? 'ph-fill' : 'ph' }} {{ $tab['icon'] }}" aria-hidden="true"></i>
                    {{ $tab['label'] }}
                    <span @class([
                        'inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-xs font-bold leading-none',
                        'bg-white/30 text-white' => $isActive,
                        $colors['badge'] => ! $isActive,
                    ])>
                        {{ $counts[$key] }}
                    </span>
                </a>
            @endforeach
        </div>

        {{-- Search --}}
        <form method="GET" action="{{ route('internal.staff.permohonan.index') }}" class="flex flex-wrap gap-2 items-end flex-1">
            <input type="hidden" name="filter" value="{{ $filter }}" />
            <div class="flex-1 min-w-[200px]">
                <x-ui.input label="" name="search" :value="$search" placeholder="Ketik NIB atau Nama PBF..." />
            </div>
            <div class="flex items-end gap-2">
                <x-ui.button type="submit" variant="default" size="md">
                    <i class="ph ph-magnifying-glass mr-1"></i> Cari
                </x-ui.button>
                @if($search)
                    <a href="{{ route('internal.staff.permohonan.index', ['filter' => $filter]) }}"
                       class="inline-flex items-center justify-center h-9 px-3 rounded-lg border border-slate-200 bg-white text-sm font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-colors">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Header --}}
<div class="flex items-center justify-between mb-3">
    <div class="flex items-center gap-2">
        <h2 class="text-base font-semibold text-slate-900">Permohonan</h2>
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-{{ $filterColor }}-100 text-{{ $filterColor }}-700 text-xs font-semibold">
            <i class="{{ $tabs[$filter]['icon'] }}" aria-hidden="true"></i>
            {{ $filterLabel }}
        </span>
        <span class="text-xs text-slate-400">({{ $permohonans->count() }} permohonan)</span>
    </div>
</div>

{{-- Table --}}
<x-ui.card>
    <x-ui.card-content class="p-0">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">No. Reg</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">PBF</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Posisi</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">SLA</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                </tr>
            </thead>
            @forelse($permohonans as $p)
            <x-ui.permohonan-row :permohonan="$p" :colspan="6">
                <td class="px-4 py-3 font-mono text-xs font-medium text-slate-900">{{ $p->no_registrasi }}</td>
                <td class="px-4 py-3">
                    <p class="font-medium text-slate-900">{{ $p->nama_pbf_snapshot }}</p>
                    <p class="text-xs text-slate-400">NIB: {{ $p->nib_snapshot }}</p>
                </td>
                <td class="px-4 py-3">
                    @php
                        $s = $p->status_saat_ini;
                        if ($s === 'didisposisikan') {
                            $posisiLabel = 'Ketua Tim';
                            $posisiColor = 'bg-purple-100 text-purple-800';
                        } elseif (in_array($s, ['proses_evaluasi', 'menunggu_surat_pengesahan'])) {
                            $posisiLabel = 'Staff';
                            $posisiColor = 'bg-cyan-100 text-cyan-800';
                        } elseif ($s === 'ditutup_pengajuan_ulang') {
                            $posisiLabel = 'Staff';
                            $posisiColor = 'bg-red-100 text-red-800';
                        } elseif (in_array($s, ['revisi_1', 'revisi_2', 'revisi_3'])) {
                            $posisiLabel = 'Pemohon';
                            $posisiColor = 'bg-amber-100 text-amber-800';
                        } else {
                            $posisiLabel = 'Pemohon';
                            $posisiColor = 'bg-emerald-100 text-emerald-800';
                        }
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $posisiColor }}">
                        {{ $posisiLabel }}
                    </span>
                </td>
                <td class="px-4 py-3"><x-ui.status-badge :status="$p->status_saat_ini" /></td>
                <td class="px-4 py-3"><x-ui.sla-badge :sla="app(\App\Services\SlaCalculator::class)->evaluasiPermohonan($p)" /></td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <x-ui.button variant="ghost" size="sm" href="{{ route('internal.permohonan.show', $p) }}">Detail</x-ui.button>
                    @if(in_array($p->status_saat_ini, ['didisposisikan', 'proses_evaluasi']))
                        <x-ui.button variant="default" size="sm" href="{{ route('internal.staff.evaluasi.edit', $p) }}">
                            Evaluasi
                        </x-ui.button>
                    @elseif(in_array($p->status_saat_ini, ['revisi_1', 'revisi_2', 'revisi_3']) && $p->revisi->flatMap->dokumenRevisi->isNotEmpty())
                        <x-ui.button variant="default" size="sm" href="{{ route('internal.staff.evaluasi.edit', $p) }}">
                            Evaluasi Ulang
                        </x-ui.button>
                    @elseif($p->status_saat_ini === 'menunggu_surat_pengesahan')
                        <x-ui.button variant="default" size="sm" href="{{ route('internal.staff.surat.edit', $p) }}">
                            Terbit Surat
                        </x-ui.button>
                    @endif
                    <x-ui.timeline-toggle />
                </td>
            </x-ui.permohonan-row>
            @empty
            <tbody>
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center text-slate-400">
                            <i class="ph ph-folder-open text-4xl mb-3 text-slate-300"></i>
                            <p class="text-sm font-medium text-slate-500">Belum ada permohonan</p>
                            <p class="text-xs text-slate-400 mt-1">Permohonan yang {{ strtolower($filterLabel) }} akan muncul di sini</p>
                        </div>
                    </td>
                </tr>
            </tbody>
            @endforelse
        </table>
        </div>
    </x-ui.card-content>
</x-ui.card>
@endsection
