@extends('layouts.internal')

@section('title', 'Disposisi')
@section('content')
<?php $pageTitle = 'Disposisi'; ?>

<div class="mb-6">
    <h2 class="text-lg font-semibold text-slate-900">Disposisi Permohonan</h2>
    <p class="text-sm text-slate-500">Kirim permohonan ke Ketua Tim Sertifikasi</p>
</div>

<form method="GET" action="{{ route('internal.kabalai.disposisi.index') }}" class="mb-4">
    <div class="flex flex-wrap items-end gap-4">
        <div class="flex-1">
            <x-ui.input label="Search" name="search" :value="request('search')" placeholder="Ketik NIB atau Nama PBF..." />
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
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tanggal</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($permohonans as $p)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3 font-mono text-xs font-medium text-slate-900">{{ $p->no_registrasi }}</td>
                    <td class="px-4 py-3 text-slate-700">{{ $p->nama_pbf_snapshot }}</td>
                    <td class="px-4 py-3"><x-ui.status-badge :status="$p->status_saat_ini" /></td>
                    <td class="px-4 py-3 text-slate-400 text-xs">{{ $p->tanggal_pengajuan?->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-right">
                        <div x-data="{ open: false }">
                            <x-ui.button variant="outline" size="sm" @click="open = !open">Disposisi</x-ui.button>
                            <div x-show="open" x-transition class="mt-2 bg-white border border-slate-200 rounded-lg p-4 shadow-lg text-left w-64">
                                <form method="POST" action="{{ route('internal.kabalai.disposisi.store', $p) }}" class="space-y-3">
                                    @csrf
                                    <x-ui.select label="Pilih Ketua Tim" name="ketua_tim_id" :options="$ketuaTimList->mapWithKeys(fn($kt) => [$kt->id => $kt->nama])->toArray()" placeholder="— Pilih —" required />
                                    <x-ui.textarea label="Catatan" name="catatan" :rows="2" placeholder="Opsional" />
                                    <div class="flex gap-2">
                                        <x-ui.button type="submit" size="sm">Kirim</x-ui.button>
                                        <x-ui.button type="button" variant="ghost" size="sm" @click="open = false">Batal</x-ui.button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5"><x-ui.empty-state title="Tidak ada permohonan baru" description="Semua permohonan sudah didisposisikan" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </x-ui.card-content>
</x-ui.card>
@endsection
