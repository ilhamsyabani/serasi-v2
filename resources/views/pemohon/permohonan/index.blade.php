@extends('layouts.pemohon')

@section('title', 'Pengajuan Saya')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-lg font-bold text-slate-900">Pengajuan Saya</h1>
        <p class="text-sm text-slate-500">Riwayat permohonan</p>
    </div>
    @if($permohonans->where('status_saat_ini', \App\Models\Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG)->isNotEmpty())
        <x-ui.button variant="default" size="sm" href="{{ route('pemohon.permohonan.create') }}">
            + Ajukan Ulang
        </x-ui.button>
    @endif
</div>

<x-ui.card>
    <x-ui.card-content class="p-0">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">No. Reg</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase hidden sm:table-cell">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($permohonans as $p)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3">
                        <p class="font-mono text-xs font-medium text-slate-900">{{ $p->no_registrasi }}</p>
                        @if($p->parent_permohonan_id)
                            <p class="text-xs text-slate-400 sm:hidden">Pengajuan ulang</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-400 text-xs hidden sm:table-cell">
                        {{ $p->tanggal_pengajuan?->format('d M Y') }}
                        @if($p->parent_permohonan_id)
                            <span class="block text-xs text-amber-600">Pengajuan ulang</span>
                        @endif
                    </td>
                    <td class="px-4 py-3"><x-ui.status-badge :status="$p->status_saat_ini" /></td>
                    <td class="px-4 py-3 text-right">
                        <x-ui.button variant="ghost" size="sm" href="{{ route('pemohon.permohonan.show', $p) }}">Detail</x-ui.button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4"><x-ui.empty-state title="Belum ada pengajuan" description="Pengajuan baru dibuat oleh Kepala Balai BBPOM." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </x-ui.card-content>
</x-ui.card>
@endsection
