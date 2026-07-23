@extends('layouts.internal')

@section('title', 'Distribusi')
@section('content')
<?php $pageTitle = 'Distribusi ke Staff'; ?>

<div class="mb-6">
    <h2 class="text-lg font-semibold text-blue-900">Distribusi Permohonan</h2>
    <p class="text-sm text-slate-500">Tugaskan permohonan yang telah didisposisikan ke Staff Sertifikasi.</p>
</div>

@if(session('success'))
    <x-ui.alert type="success" class="mb-5">{{ session('success') }}</x-ui.alert>
@endif

{{-- Ringkasan beban kerja staff, sebagai bahan pertimbangan penugasan --}}
<x-ui.card class="mb-6">
    <x-ui.card-content>
        <h3 class="text-sm font-semibold text-blue-900 mb-3">Beban Kerja Staff Saat Ini</h3>
        @if($staffList->isEmpty())
            <p class="text-sm text-slate-400">Belum ada staff sertifikasi aktif.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach($staffList as $s)
                    <div class="flex items-center gap-3 rounded-lg border border-slate-100 bg-slate-50/60 px-3 py-2">
                        <x-ui.avatar :name="$s->nama" size="sm" />
                        <span class="flex-1 truncate text-sm text-slate-700">{{ $s->nama }}</span>
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-slate-500">
                            <i class="ph ph-briefcase" aria-hidden="true"></i>{{ $bebanKerja[$s->id] ?? 0 }} aktif
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card-content>
</x-ui.card>

<x-ui.card>
    <x-ui.card-content class="p-0">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">No. Reg</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">PBF</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tanggal</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($permohonans as $p)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3 font-mono text-xs font-medium text-slate-900">{{ $p->no_registrasi }}</td>
                    <td class="px-4 py-3 text-slate-700">{{ $p->nama_pbf_snapshot }}</td>
                    <td class="px-4 py-3 text-slate-400 text-xs">{{ $p->tanggal_pengajuan?->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-right">
                        <span class="relative inline-block" x-data="{ open: false }" @keydown.escape.window="open = false">
                            <x-ui.button variant="default" size="sm" @click="open = !open">
                                <i class="ph ph-user-plus" aria-hidden="true"></i> Distribusikan
                            </x-ui.button>
                            <div x-show="open" x-cloak x-transition @click.outside="open = false"
                                 class="absolute right-0 z-20 mt-2 w-64 rounded-xl border border-slate-200 bg-white p-4 text-left shadow-lg">
                                <form method="POST" action="{{ route('internal.ketua_tim.distribusi.store', $p) }}" class="space-y-3">
                                    @csrf
                                    <x-ui.select label="Pilih Staff" name="staff_id"
                                                 :options="$staffList->mapWithKeys(fn($s) => [$s->id => $s->nama . ' (' . ($bebanKerja[$s->id] ?? 0) . ' aktif)'])->toArray()"
                                                 placeholder="— Pilih Staff —" required />
                                    <x-ui.textarea label="Catatan (opsional)" name="catatan" :rows="2" placeholder="Instruksi untuk staff" />
                                    <div class="flex gap-2">
                                        <x-ui.button type="submit" size="sm">Kirim</x-ui.button>
                                        <x-ui.button type="button" variant="ghost" size="sm" @click="open = false">Batal</x-ui.button>
                                    </div>
                                </form>
                            </div>
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4"><x-ui.empty-state title="Tidak ada yang perlu didistribusikan" description="Semua permohonan tim sudah ditugaskan ke staff." icon="✅" /></td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </x-ui.card-content>
</x-ui.card>
@endsection
