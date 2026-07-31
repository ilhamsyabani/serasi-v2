@extends('layouts.internal')

@section('title', 'Distribusi')

@section('content')
<?php $pageTitle = 'Distribusi ke Staff'; ?>

{{-- Header --}}
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h2 class="text-xl font-bold text-slate-900">Distribusi Permohonan</h2>
        <p class="text-sm text-slate-500 mt-1">Tugaskan permohonan yang telah didisposisikan ke Staff Sertifikasi.</p>
    </div>
</div>

@if(session('success'))
<x-ui.alert type="success" class="mb-6 shadow-sm">{{ session('success') }}</x-ui.alert>
@endif

{{-- Ringkasan Beban Kerja Staff --}}
<x-ui.card class="mb-6 shadow-sm border-slate-200">
    <x-ui.card-content>
        <div class="flex items-center gap-2 mb-4">
            <h3 class="text-sm text-slate-800">Beban Kerja Staff Saat Ini</h3>
        </div>

        @if($staffList->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 p-4 text-center">
            <p class="text-sm text-slate-500">Belum ada staff sertifikasi aktif.</p>
        </div>
        @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($staffList as $s)
            <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 transition-colors hover:bg-slate-100/70">
                <div class="flex items-center gap-3 overflow-hidden">
                    <x-ui.avatar :name="$s->nama" size="sm" />
                    <span class="truncate text-sm font-medium text-slate-700">{{ $s->nama }}</span>
                </div>
                {{-- Badge highlight untuk jumlah tugas --}}
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-700/10">
                    <i class="ph-bold ph-briefcase" aria-hidden="true"></i>
                    {{ $bebanKerja[$s->id] ?? 0 }}
                </span>
            </div>
            @endforeach
        </div>
        @endif
    </x-ui.card-content>
</x-ui.card>

{{-- Filter Form --}}
<form method="GET" action="{{ route('internal.ketua_tim.distribusi.index') }}">
    <div class="flex flex-wrap items-end gap-4 mb-4">
        <div class="flex-1">
            <x-ui.input
                label="Cari Permohonan"
                name="search"
                :value="request('search')"
                placeholder="Ketik NIB atau Nama PBF..." />
        </div>

        <div class="w-38">
            <x-ui.input
                label="Dari Tanggal"
                type="date"
                name="tanggal_dari"
                value="{{ $tanggalDari }}"
                placeholder="DD-MM-YYYY" />
        </div>
        <div class="w-38">
            <x-ui.input
                label="Sampai Tanggal"
                type="date"
                name="tanggal_sampai"
                value="{{ $tanggalSampai }}"
                placeholder="DD-MM-YYYY" />
        </div>
        <div class="flex gap-2">
            <x-ui.button variant="default" type="submit">Terapkan</x-ui.button>
            <x-ui.button variant="outline" type="submit" onclick="this.form.status.value=''; this.form.tanggal_dari.value=''; this.form.tanggal_sampai.value=''; this.form.search.value='';">Reset</x-ui.button>
        </div>
    </div>
</form>


{{-- Data Table --}}
<x-ui.card class="shadow-sm border-slate-200">
    <x-ui.card-content class="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">No. Reg</th>
                        <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">PBF</th>
                        <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($permohonans as $p)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-5 py-4 font-mono text-xs font-semibold text-slate-800 whitespace-nowrap">
                            {{ $p->no_registrasi }}
                        </td>
                        <td class="px-5 py-4 text-slate-700 font-medium">
                            {{ $p->nama_pbf_snapshot }}
                        </td>
                        <td class="px-5 py-4 text-slate-500 text-xs whitespace-nowrap">
                            {{ $p->tanggal_pengajuan?->format('d M Y') }}
                        </td>
                        <td class="px-5 py-4 text-right">
                            {{-- Action Dropdown dengan AlpineJS --}}
                            <span class="relative inline-block" x-data="{ open: false }" @keydown.escape.window="open = false">
                                <x-ui.button variant="default" size="sm" @click="open = !open">
                                    <i class="ph-bold ph-user-plus mr-1.5" aria-hidden="true"></i> Distribusi
                                </x-ui.button>

                                {{-- Dropdown Form Distribusi (Ditambah animasi transisi dan hierarki visual) --}}
                                <div x-show="open"
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                                    @click.outside="open = false"
                                    class="absolute right-0 z-30 mt-2 w-72 origin-top-right rounded-xl border border-slate-200 bg-white p-5 text-left shadow-xl ring-1 ring-black/5">

                                    <h4 class="text-sm font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">Tugaskan ke Staff</h4>

                                    <form method="POST" action="{{ route('internal.ketua_tim.distribusi.store', $p) }}" class="space-y-4">
                                        @csrf
                                        <div>
                                            <x-ui.select label="Pilih Staff" name="staff_id"
                                                :options="$staffList->mapWithKeys(fn($s) => [$s->id => $s->nama . ' (' . ($bebanKerja[$s->id] ?? 0) . ' tugas)'])->toArray()"
                                                placeholder="— Pilih Staff —" required />
                                        </div>
                                        <div>
                                            <x-ui.textarea label="Catatan Khusus (Opsional)" name="catatan" :rows="2" placeholder="Masukkan instruksi khusus..." />
                                        </div>
                                        <div class="flex gap-2 pt-2 border-t border-slate-50 mt-2">
                                            <x-ui.button type="submit" size="sm" class="w-full justify-center">Kirim</x-ui.button>
                                            <x-ui.button type="button" variant="ghost" size="sm" class="w-full justify-center" @click="open = false">Batal</x-ui.button>
                                        </div>
                                    </form>
                                </div>
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-5 py-10 text-center">
                            <x-ui.empty-state
                                title="Semua Telah Didistribusikan"
                                description="Tidak ada permohonan baru yang perlu ditugaskan ke staff saat ini."
                                />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-2">
                {{ $permohonans->links() }}
            </div>
        </div>
    </x-ui.card-content>
</x-ui.card>
@endsection