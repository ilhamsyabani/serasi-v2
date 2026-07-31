@extends('layouts.internal')

@section('title', 'Dashboard Staff')
@section('content')
<?php $pageTitle = 'Evaluasi & Surat'; ?>

<div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    <x-ui.stat-card :value="$permohonans->count()" label="Total Saya" description="Permohonan ditugaskan" icon="ph-files" />
    <x-ui.stat-card :value="$permohonans->whereIn('status_saat_ini', ['proses_evaluasi','revisi_1','revisi_2','revisi_3'])->count()" label="Butuh Evaluasi" description="Menunggu tinjauan" icon="ph-magnifying-glass" />
    <x-ui.stat-card :value="$permohonans->where('status_saat_ini', 'menunggu_surat_pengesahan')->count()" label="Siap Terbit" description="Upload surat" icon="ph-file-text" />
</div>

<div class="mb-6">
    <form method="GET" action="{{ route('internal.staff.dashboard') }}" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <x-ui.input label="" name="search" :value="request('search')" placeholder="Ketik NIB atau Nama PBF..." />
        </div>
        <div class="flex items-end gap-2">
            <x-ui.button type="submit">Cari</x-ui.button>
            @if(request('search'))
                <x-ui.button type="button" variant="ghost" onclick="window.location='{{ url()->current() }}'">Reset</x-ui.button>
            @endif
        </div>
    </form>
</div>

<x-ui.card>
    <x-ui.card-content class="p-0">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">No. Reg</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">PBF</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                </tr>
            </thead>
            @forelse($permohonans as $p)
            <x-ui.permohonan-row :permohonan="$p" :colspan="4">
                <td class="px-4 py-3 font-mono text-xs font-medium text-slate-900">{{ $p->no_registrasi }}</td>
                <td class="px-4 py-3 text-slate-700">{{ $p->nama_pbf_snapshot }}</td>
                <td class="px-4 py-3"><x-ui.status-badge :status="$p->status_saat_ini" /></td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    @if(in_array($p->status_saat_ini, [\App\Models\Permohonan::STATUS_PROSES_EVALUASI, \App\Models\Permohonan::STATUS_REVISI_1, \App\Models\Permohonan::STATUS_REVISI_2, \App\Models\Permohonan::STATUS_REVISI_3]))
                        <x-ui.button variant="default" size="sm" href="{{ route('internal.staff.evaluasi.edit', $p) }}">Evaluasi</x-ui.button>
                    @elseif($p->status_saat_ini === \App\Models\Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN)
                        <x-ui.button variant="default" size="sm" href="{{ route('internal.staff.surat.edit', $p) }}">Upload Surat</x-ui.button>
                    @endif
                    <x-ui.button variant="ghost" size="sm" href="{{ route('internal.permohonan.show', $p) }}">Detail</x-ui.button>
                    <x-ui.timeline-toggle />
                </td>
            </x-ui.permohonan-row>
            @empty
            <tbody>
                <tr><td colspan="4"><x-ui.empty-state title="Belum ada permohonan" description="Tidak ada permohonan yang ditugaskan ke Anda" /></td></tr>
            </tbody>
            @endforelse
        </table>
    </x-ui.card-content>
</x-ui.card>
@endsection
