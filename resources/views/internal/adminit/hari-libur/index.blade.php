@extends('layouts.internal')

@section('title', 'Hari Libur & Cuti')
@section('content')
<?php $pageTitle = 'Hari Libur & Cuti'; ?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Hari Libur & Cuti</h2>
        <p class="text-sm text-slate-500">Konfigurasi hari非 kerja untuk perhitungan SLA</p>
    </div>
    <div class="flex items-center gap-2">
        <x-ui.button variant="outline" size="sm" href="{{ route('internal.adminit.hari-libur.create') }}">+ Tambah Satu</x-ui.button>
        <x-ui.button variant="outline" size="sm" x-data @click="$dispatch('open-bulk')">+ Tambah Massal</x-ui.button>
    </div>
</div>

@if(session('success'))
    <x-ui.alert type="success" class="mb-5">{{ session('success') }}</x-ui.alert>
@endif

{{-- Bulk Modal --}}
<div x-data="{ open: false }"
     @open-bulk.window="open = true"
     x-show="open"
     x-on:keydown.escape.window="open = false"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4" @click.stop>
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-900">Tambah Hari Libur Massal</h3>
            <button @click="open = false" class="text-slate-400 hover:text-slate-600">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('internal.adminit.hari-libur.bulk-store') }}" class="p-5 space-y-4">
            @csrf
            <p class="text-sm text-slate-600">Tambahkan beberapa tanggal sekaligus untuk hari libur yang sama (mis. libur puasa, libur Natal).</p>
            <div>
                <label class="text-sm font-medium text-slate-700 mb-1.5 block">Tanggal <span class="text-red-500">*</span></label>
                <textarea name="tanggal_list" rows="4"
                    placeholder="2026-01-01&#10;2026-01-02&#10;2026-01-03"
                    class="flex w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 resize-none"
                ></textarea>
                <p class="text-xs text-slate-400 mt-1">Satukan satu tanggal per baris, format: YYYY-MM-DD</p>
            </div>
            <x-ui.input label="Keterangan" name="keterangan" placeholder="Libur nasional / Cuti bersama / ..." required />
            <div class="flex justify-end gap-2 pt-2">
                <x-ui.button type="button" variant="outline" @click="open = false">Batal</x-ui.button>
                <x-ui.button type="submit" variant="default">Simpan</x-ui.button>
            </div>
        </form>
    </div>
</div>

<x-ui.card>
    <x-ui.card-content class="p-0">
        {{-- Filter Bar --}}
        <form method="GET" class="flex flex-wrap items-end gap-3 px-4 py-3 border-b border-slate-100">
            <div class="flex-1 min-w-48">
                <x-ui.input name="search" :value="$search" placeholder="Cari keterangan..." />
            </div>
            <div class="w-36">
                <select name="tahun"
                    class="flex h-10 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500"
                    onchange="this.form.submit()">
                    @foreach($tahunList->prepend(now()->year, now()->year) as $t)
                        <option value="{{ $t }}" {{ $tahun == $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <x-ui.button type="submit" variant="outline" size="sm">Filter</x-ui.button>
            @if($search)
                <x-ui.button variant="ghost" size="sm" href="{{ route('internal.adminit.hari-libur.index', ['tahun' => $tahun]) }}">Reset</x-ui.button>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Hari</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Keterangan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($hariLiburs as $hl)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">
                            <span class="font-mono text-sm text-slate-800">{{ \Carbon\Carbon::parse($hl->tanggal)->format('d M Y') }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ \Carbon\Carbon::parse($hl->tanggal)->locale('id')->dayName }}
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $hl->keterangan }}</td>
                        <td class="px-4 py-3 text-right">
                            <x-ui.button variant="ghost" size="sm" href="{{ route('internal.adminit.hari-libur.edit', $hl) }}">Edit</x-ui.button>
                            <form action="{{ route('internal.adminit.hari-libur.destroy', $hl) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm" onclick="return confirm('Hapus hari libur ini?')" class="!text-red-600 hover:!bg-red-50">Hapus</x-ui.button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">Belum ada data hari libur.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($hariLiburs->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $hariLiburs->links() }}
            </div>
        @endif
    </x-ui.card-content>
</x-ui.card>
@endsection
