@extends('layouts.internal')

@section('title', 'Audit Trail')
@section('content')
<?php $pageTitle = 'Audit Trail'; ?>

<div class="flex items-start justify-between mb-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Riwayat Aktivitas Sistem</h2>
        <p class="text-sm text-slate-500">Log aktivitas penting di seluruh modul aplikasi</p>
    </div>
</div>

{{-- Statistik Ringkasan --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
    <x-ui.stat-card label="Total Aktivitas" :value="$stats['total']" icon="ph-activity" color="slate" />
    <x-ui.stat-card label="User Internal" :value="$stats['internal']" icon="ph-user-circle" color="blue" />
    <x-ui.stat-card label="Pemohon (PBF)" :value="$stats['pemohon']" icon="ph-storefront" color="emerald" />
    <x-ui.stat-card label="Hari Ini" :value="$stats['hari_ini']" icon="ph-calendar-check" color="amber" />
</div>

{{-- Filter --}}
<form method="GET" action="{{ route('internal.adminit.audit-trail.index') }}" class="mb-4">
    <div class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[160px]">
            <x-ui.input label="Cari" name="search" :value="request('search')" placeholder="Aksi, modul, user, no. registrasi..." />
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Tipe User</label>
            <select name="user_type" class="border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none w-36">
                <option value="">Semua</option>
                <option value="internal" {{ request('user_type') === 'internal' ? 'selected' : '' }}>Internal</option>
                <option value="pemohon" {{ request('user_type') === 'pemohon' ? 'selected' : '' }}>Pemohon</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Modul</label>
            <select name="modul" class="border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none w-40">
                <option value="">Semua</option>
                @foreach($modulList as $m)
                    <option value="{{ $m }}" {{ request('modul') === $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Permohonan</label>
            <select name="permohonan_id" class="border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none w-48">
                <option value="">Semua</option>
                @foreach($permohonanList as $p)
                    <option value="{{ $p->id }}" {{ request('permohonan_id') == $p->id ? 'selected' : '' }}>
                        {{ $p->no_registrasi }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Dari Tanggal</label>
            <input type="date" name="tanggal_dari" value="{{ request('tanggal_dari') }}"
                   class="border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Sampai Tanggal</label>
            <input type="date" name="tanggal_sampai" value="{{ request('tanggal_sampai') }}"
                   class="border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
        </div>
        <div class="flex gap-2 items-end">
            <x-ui.button variant="default" type="submit">Terapkan</x-ui.button>
            <a href="{{ route('internal.adminit.audit-trail.index') }}">
                <x-ui.button variant="outline" type="button">Reset</x-ui.button>
            </a>
        </div>
    </div>
</form>

<x-ui.card>
    <x-ui.card-content class="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Waktu</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Pelaku</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Modul</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Permohonan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Detail</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($logs as $log)
                    @php
                        $pelaku = $log->user();
                        $isInternal = $log->user_type === 'internal';
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        {{-- Waktu --}}
                        <td class="px-4 py-3 text-slate-500 text-xs whitespace-nowrap">
                            <span class="block font-medium text-slate-700">{{ $log->created_at->format('d M Y') }}</span>
                            <span class="text-slate-400">{{ $log->created_at->format('H:i:s') }}</span>
                        </td>

                        {{-- Pelaku --}}
                        <td class="px-4 py-3">
                            @if($pelaku)
                                <div class="flex items-center gap-2">
                                    <span @class([
                                        'inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium',
                                        'bg-blue-100 text-blue-700' => $isInternal,
                                        'bg-emerald-100 text-emerald-700' => !$isInternal,
                                    ])>
                                        @if($isInternal)
                                            <i class="ph ph-user-circle text-xs" aria-hidden="true"></i>{{ $pelaku->nama ?? '-' }}
                                        @else
                                            <i class="ph ph-storefront text-xs" aria-hidden="true"></i>{{ $pelaku->nama_pbf ?? '-' }}
                                        @endif
                                    </span>
                                </div>
                                <span class="text-xs text-slate-400">{{ $pelaku->email ?? ($pelaku->no_whatsapp ?? '-') }}</span>
                            @else
                                <span class="text-xs text-slate-400">User dihapus</span>
                            @endif
                        </td>

                        {{-- Aksi --}}
                        <td class="px-4 py-3">
                            <span class="font-medium text-slate-800 text-xs" style="word-break: break-all;">
                                {{ $log->aksi }}
                            </span>
                        </td>

                        {{-- Modul --}}
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-medium bg-slate-100 text-slate-600">
                                {{ $log->modul }}
                            </span>
                        </td>

                        {{-- Permohonan --}}
                        <td class="px-4 py-3">
                            @if($log->permohonan)
                                <a href="{{ route('internal.permohonan.show', $log->permohonan) }}"
                                   class="font-mono text-xs text-blue-600 hover:underline"
                                   title="{{ $log->permohonan->nama_pbf_snapshot }}">
                                    {{ $log->permohonan->no_registrasi }}
                                </a>
                            @else
                                <span class="text-xs text-slate-400">-</span>
                            @endif
                        </td>

                        {{-- Detail --}}
                        <td class="px-4 py-3">
                            @if($log->detail)
                                @php
                                    $detail = is_array($log->detail) ? $log->detail : json_decode($log->detail, true);
                                @endphp
                                @if(is_array($detail) && count($detail) > 0)
                                    <div x-data="{ open: false }">
                                        <button @click="open = !open"
                                                class="inline-flex items-center gap-1 text-xs text-slate-600 hover:text-slate-900">
                                            <i class="ph text-xs" :class="open ? 'ph-caret-up' : 'ph-caret-down'" aria-hidden="true"></i>
                                            {{ count($detail) }} field
                                        </button>
                                        <div x-show="open" x-collapse class="mt-1.5 bg-slate-800 text-slate-100 rounded-lg p-2 text-xs font-mono whitespace-pre-wrap max-w-xs overflow-x-auto">
                                            {{ json_encode($detail, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            @else
                                <span class="text-xs text-slate-400">-</span>
                            @endif
                        </td>

                        {{-- IP --}}
                        <td class="px-4 py-3">
                            <span class="text-xs text-slate-400 font-mono">{{ $log->ip_address ?? '-' }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <i class="ph ph-clipboard-text text-4xl text-slate-300 mb-3" aria-hidden="true"></i>
                                <p class="text-sm font-medium text-slate-500">Belum ada aktivitas</p>
                                <p class="text-xs text-slate-400 mt-1">Log aktivitas akan muncul saat ada aksi penting di sistem.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card-content>
</x-ui.card>

@if($logs->hasPages())
<div class="mt-4">
    {{ $logs->withQueryString()->links() }}
</div>
@endif
@endsection
