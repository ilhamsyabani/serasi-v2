@extends('layouts.internal')

@section('title', 'Pengajuan Ulang')
@section('content')
<?php $pageTitle = 'Pengajuan Ulang'; ?>

<div class="mb-6">
    <h2 class="text-lg font-semibold text-slate-900">Pengajuan Ulang</h2>
    <p class="text-sm text-slate-500">Ajukan ulang permohonan yang ditutup setelah kuota {{ \App\Models\Permohonan::MAKS_REVISI }} revisi habis. Akun PBF tetap sama — cukup unggah dokumen perbaikan.</p>
</div>

{{-- Tab --}}
<div class="mb-4 flex flex-wrap items-end justify-between gap-4">
    <div class="inline-flex rounded-lg bg-slate-100 p-1 text-sm">
        @foreach(['perlu' => 'Perlu Diajukan Ulang', 'sudah' => 'Sudah Diajukan Ulang'] as $key => $label)
            <a href="{{ route('internal.kabalai.pengajuan-ulang.index', ['tab' => $key]) }}"
               @class([
                   'rounded-md px-3 py-1.5 font-medium transition-colors',
                   'bg-white text-blue-900 shadow-sm' => $tab === $key,
                   'text-slate-500 hover:text-slate-800' => $tab !== $key,
               ])>
                {{ $label }}
                @if($key === 'perlu' && $jumlahPerlu > 0)
                    <span class="ml-1 rounded-full bg-amber-100 px-1.5 text-xs font-semibold text-amber-700">{{ $jumlahPerlu }}</span>
                @endif
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('internal.kabalai.pengajuan-ulang.index') }}" class="flex items-end gap-2">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="w-64">
            <x-ui.input label="Cari" name="search" :value="$search" placeholder="No. Reg, NIB, atau nama PBF..." />
        </div>
        <x-ui.button type="submit" variant="default">Cari</x-ui.button>
    </form>
</div>

<x-ui.card>
    <x-ui.card-content class="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">No. Reg</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">PBF</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tgl Ditutup</th>
                        @if($tab === 'perlu')
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Alasan Ditutup</th>
                        @else
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Pengajuan Ulang</th>
                        @endif
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($permohonans as $p)
                        @php
                            $tglTutup = $p->statusLog->firstWhere('status', \App\Models\Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG)?->waktu_mulai;
                            $anak = $p->pengajuanUlang->first();
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors align-top">
                            <td class="px-4 py-3 font-mono text-xs font-medium text-slate-900 whitespace-nowrap">{{ $p->no_registrasi }}</td>
                            <td class="px-4 py-3">
                                <p class="text-slate-700">{{ $p->nama_pbf_snapshot }}</p>
                                <p class="text-xs text-slate-400">NIB {{ $p->nib_snapshot }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">{{ $tglTutup?->format('d M Y') ?? '—' }}</td>
                            @if($tab === 'perlu')
                                <td class="px-4 py-3 text-xs text-slate-600 max-w-md">
                                    <p class="line-clamp-2" title="{{ $service->alasanDitutup($p) }}">{{ $service->alasanDitutup($p) ?? '—' }}</p>
                                </td>
                            @else
                                <td class="px-4 py-3 text-xs">
                                    @if($anak)
                                        <a href="{{ route('internal.kabalai.permohonan.show', $anak) }}" class="font-mono text-emerald-700 hover:underline">{{ $anak->no_registrasi }}</a>
                                        <p class="text-slate-400">oleh {{ $anak->dibuat_oleh_tipe === \App\Models\Permohonan::DIBUAT_OLEH_PEMOHON ? 'Pemohon (mandiri)' : 'Kepala Balai' }} · {{ $anak->tanggal_pengajuan?->format('d M Y') }}</p>
                                    @endif
                                </td>
                            @endif
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <x-ui.button variant="ghost" size="sm" href="{{ route('internal.kabalai.permohonan.show', $p) }}">Detail</x-ui.button>
                                @if($tab === 'perlu')
                                    <x-ui.button variant="default" size="sm" href="{{ route('internal.kabalai.pengajuan-ulang.create', $p) }}">
                                        <i class="ph ph-arrows-clockwise mr-1" aria-hidden="true"></i> Ajukan Ulang
                                    </x-ui.button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">
                            <x-ui.empty-state
                                :title="$tab === 'perlu' ? 'Tidak ada permohonan yang perlu diajukan ulang' : 'Belum ada pengajuan ulang'"
                                :description="$tab === 'perlu' ? 'Permohonan yang ditutup setelah kuota revisi habis akan muncul di sini.' : ''" />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($permohonans->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">{{ $permohonans->links() }}</div>
        @endif
    </x-ui.card-content>
</x-ui.card>
@endsection
