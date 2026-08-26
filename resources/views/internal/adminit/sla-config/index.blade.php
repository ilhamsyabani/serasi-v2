@extends('layouts.internal')

@section('title', 'Konfigurasi SLA')
@section('content')
<?php $pageTitle = 'Konfigurasi SLA'; ?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Konfigurasi SLA</h2>
        <p class="text-sm text-slate-500">Pengaturan target waktu per tahap pengajuan</p>
    </div>
</div>

<x-ui.card>
    <x-ui.card-content class="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tahap</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Durasi</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Satuan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Clock-off</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($configs as $config)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-3">
                            <span class="font-medium text-slate-900">{{ $config->nama_tahap }}</span>
                            <span class="block text-xs text-slate-400 font-mono">{{ $config->kode_tahap }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($config->durasi !== null)
                                <span class="font-semibold text-slate-900">{{ $config->durasi }}</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $config->satuan === 'hari_kerja' ? 'bg-cyan-50 text-cyan-700' : 'bg-orange-50 text-orange-700' }}">
                                {{ $config->satuan === 'hari_kerja' ? 'Hari Kerja' : 'Hari Kalender' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($config->clock_off)
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">
                                    <i class="ph ph-pause-circle text-xs" aria-hidden="true"></i> Ya
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
                                    Tidak
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($config->is_active)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                    <i class="ph-fill ph-check-circle text-xs" aria-hidden="true"></i> Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
                                    Nonaktif
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <x-ui.button variant="ghost" size="sm" href="{{ route('internal.adminit.sla-config.edit', $config) }}">
                                <i class="ph ph-pencil-simple mr-1" aria-hidden="true"></i> Edit
                            </x-ui.button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card-content>
</x-ui.card>
@endsection
