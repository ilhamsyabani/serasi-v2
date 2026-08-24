@extends('layouts.internal')

@section('title', 'Edit SLA - ' . $slaConfig->nama_tahap)
@section('content')
<?php $pageTitle = 'Edit SLA'; ?>

<div class="mb-6">
    <a href="{{ route('internal.adminit.sla-config.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 mb-3">
        <i class="ph ph-arrow-left" aria-hidden="true"></i> Kembali
    </a>
    <h2 class="text-lg font-semibold text-slate-900">{{ $slaConfig->nama_tahap }}</h2>
    <p class="text-sm text-slate-500">Ubah durasi target SLA untuk tahap ini</p>
</div>

@if($errors->any())
    <x-ui.alert type="error" class="mb-5">{{ $errors->first() }}</x-ui.alert>
@endif

<x-ui.card>
    <x-ui.card-header title="Durasi & Satuan" description="Field bertanda (*) wajib diisi" />
    <x-ui.card-content>
        <form method="POST" action="{{ route('internal.adminit.sla-config.update', $slaConfig) }}" class="space-y-5 max-w-md">
            @csrf @method('PUT')

            <div>
                <label class="text-sm font-medium text-slate-700 mb-1.5 block">
                    Durasi <span class="text-red-500">*</span>
                </label>
                <input
                    type="number"
                    name="durasi"
                    value="{{ old('durasi', $slaConfig->durasi) }}"
                    min="0"
                    max="999"
                    placeholder="Kosongkan untuk clock-off / tanpa SLA"
                    class="flex h-10 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500"
                />
                <p class="text-xs text-slate-400 mt-1">Kosongkan atau 0 untuk tahap clock-off / tanpa alokasi SLA</p>
            </div>

            <div>
                <label class="text-sm font-medium text-slate-700 mb-1.5 block">
                    Satuan <span class="text-red-500">*</span>
                </label>
                <select
                    name="satuan"
                    class="flex h-10 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500">
                    <option value="hari_kerja" {{ old('satuan', $slaConfig->satuan) === 'hari_kerja' ? 'selected' : '' }}>Hari Kerja</option>
                    <option value="hari_kalender" {{ old('satuan', $slaConfig->satuan) === 'hari_kalender' ? 'selected' : '' }}>Hari Kalender</option>
                </select>
                <p class="text-xs text-slate-400 mt-1">Hari Kerja mengabaikan akhir pekan & hari libur nasional</p>
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" class="sr-only peer" {{ old('is_active', $slaConfig->is_active) ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-500/30 rounded-full peer peer-checked:after:translate-x-5 rtl:peer-checked:after:-translate-x-5 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                    <span class="ms-3 text-sm font-medium text-slate-700">Aktif</span>
                </label>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <x-ui.button type="submit" variant="default">Simpan</x-ui.button>
                <x-ui.button variant="outline" href="{{ route('internal.adminit.sla-config.index') }}">Batal</x-ui.button>
            </div>
        </form>
    </x-ui.card-content>
</x-ui.card>
@endsection
