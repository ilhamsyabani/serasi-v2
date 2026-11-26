@extends('layouts.internal')

@section('title', 'Laporan & Ekspor')
@section('content')
<?php $pageTitle = 'Laporan & Ekspor'; ?>

<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Laporan Permohonan</h2>
        <p class="text-sm text-slate-500">
            @if(auth()->user()->isKetuaTim())
                Permohonan yang didisposisikan ke tim Anda.
            @else
                Permohonan yang sedang Anda evaluasi.
            @endif
            Ekspor mengikuti filter yang sedang aktif.
        </p>
    </div>
    <div class="flex gap-2">
        <x-ui.button variant="outline" href="{{ route('internal.laporan.export-pdf', request()->query()) }}">
            <i class="ph ph-file-pdf text-base" aria-hidden="true"></i> Ekspor PDF
        </x-ui.button>
        <x-ui.button variant="default" href="{{ route('internal.laporan.export', request()->query()) }}">
            <i class="ph ph-file-xls text-base" aria-hidden="true"></i> Ekspor Excel
        </x-ui.button>
    </div>
</div>

{{-- Filter --}}
<form method="GET" action="{{ route('internal.laporan.index') }}" class="mb-4">
    <div class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[180px]">
            <x-ui.input label="Cari" name="search" :value="$filter['search'] ?? ''" placeholder="No. registrasi, nama PBF, NIB..." />
        </div>
        <div>
            <label for="status" class="block text-xs font-medium text-slate-600 mb-1">Status</label>
            <select id="status" name="status" class="border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none w-52">
                <option value="">Semua</option>
                @foreach($statusList as $kode => $label)
                    <option value="{{ $kode }}" @selected(($filter['status'] ?? '') === $kode)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="tanggal_dari" class="block text-xs font-medium text-slate-600 mb-1">Pengajuan Dari</label>
            <input type="date" id="tanggal_dari" name="tanggal_dari" value="{{ $filter['tanggal_dari'] ?? '' }}"
                   class="border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
        </div>
        <div>
            <label for="tanggal_sampai" class="block text-xs font-medium text-slate-600 mb-1">Sampai</label>
            <input type="date" id="tanggal_sampai" name="tanggal_sampai" value="{{ $filter['tanggal_sampai'] ?? '' }}"
                   class="border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
        </div>
        <div class="flex gap-2 items-end">
            <x-ui.button variant="default" type="submit">Terapkan</x-ui.button>
            <x-ui.button variant="outline" href="{{ route('internal.laporan.index') }}">Reset</x-ui.button>
        </div>
    </div>
    @if($errors->any())
        <p class="mt-2 text-xs text-red-600">{{ $errors->first() }}</p>
    @endif
</form>

{{-- Ringkasan per status --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
    <x-ui.stat-card label="Total" :value="$total" icon="ph-files" color="slate" />
    @foreach($ringkasan as $kode => $r)
        @continue($r['jumlah'] === 0)
        <x-ui.stat-card :label="$r['label']" :value="$r['jumlah']" icon="ph-chart-bar" :color="match (true) {
            str_starts_with($kode, 'revisi_') => 'amber',
            $kode === 'ditutup_pengajuan_ulang' => 'red',
            $kode === 'terbit_surat_pengesahan' => 'emerald',
            default => 'blue',
        }" />
    @endforeach
</div>

<x-ui.card>
    <x-ui.card-content class="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        @foreach(['No', 'No. Registrasi', 'Nama PBF', 'Tgl Pengajuan', 'Status', 'Revisi', 'Evaluator', 'Evaluasi Terakhir', 'No. Surat', 'SLA'] as $h)
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase whitespace-nowrap">{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($permohonan as $i => $p)
                        @php $b = $baris[$i]; @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-3 text-slate-500">{{ $nomorAwal + $i }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="{{ route('internal.permohonan.show', $p) }}" class="font-medium text-slate-800 hover:text-emerald-600">{{ $p->no_registrasi }}</a>
                                @if($p->isPengajuanUlang())
                                    <p class="text-xs text-slate-400">Ulang dari {{ $p->pengajuanAsal?->no_registrasi ?? '-' }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $p->nama_pbf_snapshot }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $b[4] }}</td>
                            <td class="px-4 py-3"><x-ui.status-badge :status="$p->status_saat_ini" /></td>
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $b[7] }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $b[9] ?: '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs">
                                @if($b[11])
                                    <span class="font-medium {{ $b[11] === 'Lengkap' ? 'text-emerald-600' : 'text-red-600' }}">{{ $b[11] }}</span>
                                    <span class="text-slate-400">· {{ $b[10] }}</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $b[12] ?: '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600">{{ $b[14] ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10">
                            <x-ui.empty-state title="Tidak ada data" description="Tidak ada permohonan yang cocok dengan filter." />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card-content>
</x-ui.card>

<div class="mt-4">{{ $permohonan->links() }}</div>
@endsection
