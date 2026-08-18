@extends('layouts.internal')

@section('title', 'Dashboard')
@section('content')
<?php $pageTitle = 'Evaluasi & Surat'; ?>

@php
$roleBucket = [
    'kabalai' => [],
    'katim'   => [],
    'staff'   => [],
    'pemohon' => [],
];
foreach ($allPermohonans as $p) {
    $s = $p->status_saat_ini;
    if ($s === 'pengajuan') {
        $roleBucket['kabalai'][] = $p;
    } elseif ($s === 'didisposisikan') {
        $roleBucket['katim'][] = $p;
    } elseif (in_array($s, ['proses_evaluasi', 'menunggu_surat_pengesahan'])) {
        $roleBucket['staff'][] = $p;
    } elseif ($s === 'ditutup_pengajuan_ulang') {
        $roleBucket['staff'][] = $p;
    } elseif (in_array($s, ['revisi_1', 'revisi_2', 'revisi_3'])) {
        $roleBucket['pemohon'][] = $p;
    } elseif ($s === 'terbit_surat_pengesahan') {
        $roleBucket['pemohon'][] = $p;
    }
}

$counts = $allPermohonans->countBy('status_saat_ini');
$onProcess = $allPermohonans->whereNotIn('status_saat_ini', ['terbit_surat_pengesahan', 'ditutup_pengajuan_ulang'])->count();
@endphp

{{-- Statistik Permohonan (paling atas) --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-sm">
        <p class="text-xs font-medium text-blue-100 uppercase tracking-wide">Total Permohonan</p>
        <p class="text-2xl font-bold mt-1">{{ $allPermohonans->count() }}</p>
        <p class="text-xs text-blue-200 mt-1">seluruh permohonan</p>
    </div>
    <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white shadow-sm">
        <p class="text-xs font-medium text-emerald-100 uppercase tracking-wide">Terbit</p>
        <p class="text-2xl font-bold mt-1">{{ $counts['terbit_surat_pengesahan'] ?? 0 }}</p>
        <p class="text-xs text-emerald-200 mt-1">surat pengesahan</p>
    </div>
    <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-4 text-white shadow-sm">
        <p class="text-xs font-medium text-amber-100 uppercase tracking-wide">On Process</p>
        <p class="text-2xl font-bold mt-1">{{ $onProcess }}</p>
        <p class="text-xs text-amber-200 mt-1">sedang diproses</p>
    </div>
</div>

{{-- Keterangan Role & Status --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    {{-- Keterangan Role --}}
    <x-ui.card>
        <x-ui.card-header title="Keterangan Role" />
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-100">
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Kepala Balai</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = count($roleBucket['kabalai']); @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-blue-100 px-2 text-xs font-semibold text-blue-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Ketua Tim Sertifikasi</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = count($roleBucket['katim']); @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-purple-100 px-2 text-xs font-semibold text-purple-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Staff Sertifikasi</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = count($roleBucket['staff']); @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-cyan-100 px-2 text-xs font-semibold text-cyan-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Pemohon (PBF)</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = count($roleBucket['pemohon']); @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-amber-100 px-2 text-xs font-semibold text-amber-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-ui.card>

    {{-- Keterangan Status --}}
    <x-ui.card>
        <x-ui.card-header title="Keterangan Status" />
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-100">
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Pengajuan</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = $counts['pengajuan'] ?? 0; @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-blue-100 px-2 text-xs font-semibold text-blue-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Didiposisisikan</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = $counts['didisposisikan'] ?? 0; @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-purple-100 px-2 text-xs font-semibold text-purple-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Proses Evaluasi</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = $counts['proses_evaluasi'] ?? 0; @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-cyan-100 px-2 text-xs font-semibold text-cyan-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Revisi 1</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = $counts['revisi_1'] ?? 0; @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-amber-100 px-2 text-xs font-semibold text-amber-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Revisi 2</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = $counts['revisi_2'] ?? 0; @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-amber-100 px-2 text-xs font-semibold text-amber-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Revisi 3</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = $counts['revisi_3'] ?? 0; @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-amber-100 px-2 text-xs font-semibold text-amber-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Menunggu Surat</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = $counts['menunggu_surat_pengesahan'] ?? 0; @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-violet-100 px-2 text-xs font-semibold text-violet-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Terbit Surat</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = $counts['terbit_surat_pengesahan'] ?? 0; @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-emerald-100 px-2 text-xs font-semibold text-emerald-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Ditutup</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = $counts['ditutup_pengajuan_ulang'] ?? 0; @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-red-100 px-2 text-xs font-semibold text-red-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>

{{-- Search --}}
<div class="mb-4">
    <form method="GET" action="{{ route('internal.staff.dashboard') }}" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <x-ui.input label="" name="search" :value="$search" placeholder="Ketik NIB atau Nama PBF..." />
        </div>
        <div class="flex items-end gap-2">
            <x-ui.button type="submit">Cari</x-ui.button>
            @if($search)
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ url()->current() }}'">Reset</x-ui.button>
            @endif
        </div>
    </form>
</div>

{{-- Daftar Permohonan --}}
<div class="flex items-center justify-between mb-3">
    <h2 class="text-base font-semibold text-blue-900">Permohonan</h2>
</div>

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
                <td class="px-4 py-3 text-slate-700">{{ $p->nama_pbf_snapshot }}</td>
                <td class="px-4 py-3">
                    @php
                        $s = $p->status_saat_ini;
                        if ($s === 'pengajuan') {
                            $posisiLabel = 'Kepala Balai';
                            $posisiColor = 'bg-blue-100 text-blue-800';
                        } elseif ($s === 'didisposisikan') {
                            $posisiLabel = 'Ketua Tim';
                            $posisiColor = 'bg-purple-100 text-purple-800';
                        } elseif (in_array($s, ['proses_evaluasi', 'menunggu_surat_pengesahan', 'ditutup_pengajuan_ulang'])) {
                            $posisiLabel = 'Staff';
                            $posisiColor = 'bg-cyan-100 text-cyan-800';
                        } else {
                            $posisiLabel = 'Pemohon';
                            $posisiColor = 'bg-amber-100 text-amber-800';
                        }
                    @endphp
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $posisiColor }}">{{ $posisiLabel }}</span>
                </td>
                <td class="px-4 py-3"><x-ui.status-badge :status="$p->status_saat_ini" /></td>
                <td class="px-4 py-3">
                    <x-ui.sla-badge :sla="app(\App\Services\SlaCalculator::class)->evaluasiPermohonan($p)" />
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    @if($p->status_saat_ini === \App\Models\Permohonan::STATUS_PROSES_EVALUASI)
                        <x-ui.button variant="default" size="sm" href="{{ route('internal.staff.evaluasi.edit', $p) }}">Evaluasi</x-ui.button>
                    @elseif(in_array($p->status_saat_ini, [\App\Models\Permohonan::STATUS_REVISI_1, \App\Models\Permohonan::STATUS_REVISI_2, \App\Models\Permohonan::STATUS_REVISI_3]) && $p->revisi->flatMap->dokumenRevisi->isNotEmpty())
                        <x-ui.button variant="default" size="sm" href="{{ route('internal.staff.evaluasi.edit', $p) }}">Evaluasi Ulang</x-ui.button>
                    @elseif($p->status_saat_ini === \App\Models\Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN)
                        <x-ui.button variant="default" size="sm" href="{{ route('internal.staff.surat.edit', $p) }}">Upload Surat</x-ui.button>
                    @endif
                    <x-ui.button variant="ghost" size="sm" href="{{ route('internal.permohonan.show', $p) }}">Detail</x-ui.button>
                    <x-ui.timeline-toggle />
                </td>
            </x-ui.permohonan-row>
            @empty
            <tbody>
                <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">Belum ada permohonan yang ditugaskan ke Anda.</td></tr>
            </tbody>
            @endforelse
        </table>
        </div>
    </x-ui.card-content>
</x-ui.card>
@endsection
