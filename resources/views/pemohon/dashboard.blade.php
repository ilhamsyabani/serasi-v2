@extends('layouts.pemohon')

@section('title', 'Dashboard')

@section('content')
{{-- Greeting --}}
<div class="mb-6">
    <h1 class="text-xl font-bold text-slate-900">Halo, {{ $pbf->nama_pbf }}</h1>
    <p class="text-sm text-slate-500">Selamat datang di Portal Pemohon</p>
</div>

{{-- Active Permohonan Card --}}
@if($permohonanAktif)
    <x-ui.card class="mb-6 border-l-4 border-l-emerald-700">
        <x-ui.card-header :title="$permohonanAktif->no_registrasi" description="Permohonan aktif" />
        <x-ui.card-content>
            <div class="flex items-center justify-between mb-3">
                <x-ui.status-badge :status="$permohonanAktif->status_saat_ini" />
                <span class="text-xs text-slate-400">{{ $permohonanAktif->tanggal_pengajuan?->format('d M Y') }}</span>
            </div>

            <p class="text-sm text-slate-600 mb-4">
                @if(in_array($permohonanAktif->status_saat_ini, [\App\Models\Permohonan::STATUS_REVISI_1, \App\Models\Permohonan::STATUS_REVISI_2, \App\Models\Permohonan::STATUS_REVISI_3]))
                    📝 Permohonan Anda memerlukan perbaikan. Silakan upload revisi.
                @elseif($permohonanAktif->status_saat_ini === \App\Models\Permohonan::STATUS_TERBIT_SURAT_PENGESAHAN)
                    🎉 Selamat! Surat pengesahan telah terbit.
                @else
                    🔄 Permohonan Anda sedang dalam proses.
                @endif
            </p>

            <div class="flex flex-wrap gap-2">
                <x-ui.button variant="default" size="sm" href="{{ route('pemohon.permohonan.show', $permohonanAktif) }}">Lihat Detail</x-ui.button>
                @if(in_array($permohonanAktif->status_saat_ini, [\App\Models\Permohonan::STATUS_REVISI_1, \App\Models\Permohonan::STATUS_REVISI_2, \App\Models\Permohonan::STATUS_REVISI_3]))
                    <x-ui.button variant="outline" size="sm" href="{{ route('pemohon.revisi.show', $permohonanAktif) }}">Upload Revisi</x-ui.button>
                @endif
            </div>
        </x-ui.card-content>
    </x-ui.card>
@else
    <x-ui.card class="mb-6">
        <x-ui.card-content>
            <x-ui.empty-state title="Tidak ada permohonan aktif" description="Belum ada permohonan yang sedang diproses." icon="📋" />
        </x-ui.card-content>
    </x-ui.card>
@endif

{{-- Riwayat --}}
<div class="mb-20 sm:mb-6">
    <h2 class="text-base font-semibold text-slate-900 mb-4">Riwayat Pengajuan</h2>

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
                    @forelse($riwayat as $p)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs font-medium text-slate-900">{{ $p->no_registrasi }}</td>
                        <td class="px-4 py-3 text-slate-400 text-xs hidden sm:table-cell">{{ $p->tanggal_pengajuan?->format('d M Y') }}</td>
                        <td class="px-4 py-3"><x-ui.status-badge :status="$p->status_saat_ini" /></td>
                        <td class="px-4 py-3 text-right">
                            <x-ui.button variant="ghost" size="sm" href="{{ route('pemohon.permohonan.show', $p) }}">Detail</x-ui.button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4"><x-ui.empty-state title="Belum ada riwayat" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.card-content>
    </x-ui.card>
</div>
@endsection
