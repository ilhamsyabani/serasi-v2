@extends('layouts.internal')

@section('title', 'Permohonan')
@section('content')
<?php $pageTitle = 'Permohonan'; ?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Permohonan Saya</h2>
        <p class="text-sm text-slate-500">Permohonan yang saya input</p>
    </div>
    <x-ui.button variant="default" href="{{ route('internal.kabalai.permohonan.create') }}">
        + Input Permohonan Baru
    </x-ui.button>
</div>

<form method="GET" action="{{ route('internal.kabalai.permohonan.index') }}" class="mb-4">
    <div class="flex flex-wrap items-end gap-4">
        <div class="flex-1">
            <x-ui.input label="Search" name="search" :value="request('search')" placeholder="Ketik NIB atau Nama PBF..."/>
        </div>
        <div class="w-64">
            <x-ui.select
                label="Status"
                name="status"
                :options="['' => 'Semua Status'] + \App\Models\Permohonan::statusList()"
                :selected="$status"
            />
        </div>
        <div class="w-38">
            <x-ui.input
                label="Dari Tanggal"
                type="date"
                name="tanggal_dari"
                value="{{ $tanggalDari }}"
                placeholder="DD-MM-YYYY"
            />
        </div>
        <div class="w-38">
            <x-ui.input
                label="Sampai Tanggal"
                type="date"
                name="tanggal_sampai"
                value="{{ $tanggalSampai }}"
                placeholder="DD-MM-YYYY"
            />
        </div>
        <div class="flex gap-2">
            <x-ui.button variant="default" type="submit">Terapkan</x-ui.button>
            <x-ui.button variant="outline" type="submit" onclick="this.form.status.value=''; this.form.tanggal_dari.value=''; this.form.tanggal_sampai.value=''; this.form.search.value='';" >Reset</x-ui.button>
        </div>
    </div>
</form>

<x-ui.card>
    <x-ui.card-content class="p-0">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">No. Reg</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">PBF</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'status_saat_ini', 'dir' => ($sort === 'status_saat_ini' && $dir === 'asc') ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 hover:text-slate-700">
                            Status
                            @if($sort === 'status_saat_ini')
                                <span class="text-[10px]">{{ $dir === 'asc' ? '▲' : '▼' }}</span>
                            @else
                                <span class="text-[10px] text-slate-300">⇅</span>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'tanggal_pengajuan', 'dir' => ($sort === 'tanggal_pengajuan' && $dir === 'asc') ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 hover:text-slate-700">
                            Tanggal
                            @if($sort === 'tanggal_pengajuan')
                                <span class="text-[10px]">{{ $dir === 'asc' ? '▲' : '▼' }}</span>
                            @else
                                <span class="text-[10px] text-slate-300">⇅</span>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                </tr>
            </thead>
            @forelse($permohonans as $p)
            <x-ui.permohonan-row :permohonan="$p" :colspan="5">
                <td class="px-4 py-3 font-mono text-xs font-medium text-slate-900">{{ $p->no_registrasi }}</td>
                <td class="px-4 py-3">
                    <p class="font-medium text-slate-900">{{ $p->nama_pbf_snapshot }}</p>
                    <p class="text-xs text-slate-400">{{ $p->nib_snapshot }}</p>
                </td>
                <td class="px-4 py-3"><x-ui.status-badge :status="$p->status_saat_ini" /></td>
                <td class="px-4 py-3 text-slate-400 text-xs">{{ $p->tanggal_pengajuan?->format('d M Y') }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <x-ui.button variant="ghost" size="sm" href="{{ route('internal.kabalai.permohonan.show', $p) }}">Detail</x-ui.button>
                    <x-ui.timeline-toggle />
                </td>
            </x-ui.permohonan-row>
            @empty
            <tbody>
                <tr><td colspan="5"><x-ui.empty-state title="Belum ada permohonan" description="Klik tombol di atas untuk input permohonan baru" /></td></tr>
            </tbody>
            @endforelse
        </table>
        <div class="mt-2">
            {{ $permohonans->links() }}
        </div>
    </x-ui.card-content>
</x-ui.card>
@endsection
